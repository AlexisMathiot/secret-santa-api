# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Secret Santa API** built with **Symfony 7.0** and **PHP 8.2+**, running in Docker containers. It manages Secret Santa events where users can create events, invite participants, assign gift recipients randomly, and manage gift lists.

## Development Environment

### Starting the Application

```bash
# Build and start containers
docker-compose build
docker-compose up

# Stop containers
docker-compose down
```

### Accessing Services

- **API**: http://localhost:8080 (PHP/Apache container)
- **Database**: MySQL 8.0 on port 4306
  - User: `symfony` / Password: `pass`
  - Database: `symfony`
- **phpMyAdmin**: http://localhost:8082
- **Maildev** (email testing): http://localhost:8081

### Initial Setup (First Time Only)

After cloning the repository, perform these one-time setup steps:

```bash
# 1. Start Docker containers
docker-compose up -d

# 2. Install dependencies
docker exec php composer install

# 3. Create .env.local file (copy from .env.local.example or create manually)
# See "Environment Configuration" section below for required variables

# 4. Generate JWT keys
docker exec php php bin/console lexik:jwt:generate-keypair

# 5. Run database migrations
docker exec php php bin/console doctrine:migrations:migrate

# 6. Load fixtures (optional - for development data)
docker exec php php bin/console doctrine:fixtures:load
```

### Working Inside the PHP Container

All Symfony commands must be run inside the PHP container:

```bash
# Enter the container
docker exec -it php /bin/bash

# Install dependencies
composer install

# Database operations
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load

# Clear cache
php bin/console cache:clear

# Create entities/controllers
php bin/console make:entity
php bin/console make:controller
```

### Running Tests

```bash
# Inside the PHP container
php bin/phpunit

# Run specific test file
php bin/phpunit tests/Functional/Security/GoogleOAuthTest.php
```

Tests use:
- PHPUnit 9.5+
- `DAMA\DoctrineTestBundle` for database transaction rollback between tests
- Symfony test environment configuration

## Architecture

### Core Domain Model

The application revolves around Secret Santa event management:

1. **Event**: A Secret Santa event with participants and an organizer
2. **User**: Participants who can join events and create gift lists
3. **Santa**: Assignment linking who gives to whom (User → User for an Event)
4. **GiftList**: Each user's wishlist for a specific event
5. **Gift**: Individual gift items in a gift list
6. **Invitation**: Token-based invitations to join events (expire after 7 days)

### Key Relationships

- **Event ↔ User**: Many-to-many (participants)
- **Event → User**: Many-to-one (organizer)
- **Santa**: Links Event + User (giver) → User (receiver)
- **GiftList**: Links User + Event (one list per user per event)
- **Invitation**: Links sender User + receiver User + Event

### Santa Assignment Algorithm

The core Secret Santa logic is in `EventController::setSanta()` (app/src/Controller/EventController.php:317):
- Shuffles all event participants
- Creates a circular assignment where each user gives to the next user in the shuffled array
- The last user gives to the first user (circular loop)
- Requires at least 2 participants

### Security & Authentication

**Authentication Methods:**
1. **JWT Authentication**: Standard email/password login via `/api/login`
   - Token TTL: 86400 seconds (24 hours)
   - User identified by email field
   - JWT keys stored in `app/config/jwt/`

2. **Google OAuth2**: Social login via Google
   - Configured in `app/config/packages/knpu_oauth2_client.yaml`
   - Custom authenticator: `App\Security\GoogleAuthenticator`
   - OAuth service: `App\Security\OAuthRegistrationService`

**Authorization:**
- `EventVoter` controls Event operations (edit/delete)
- Only event organizers or ROLE_ADMIN can modify/delete events
- API routes (except `/oauth`, `/api/login`, `/forgot-password`, `/reset-password`) require `ROLE_USER`

**OAuth Google Flow:**
1. User clicks "Login with Google" → Redirects to `/oauth/connect/google`
2. Backend generates Google OAuth URL and redirects to Google
3. User authenticates on Google's page
4. Google redirects back to `/oauth/check/google?code=...`
5. `GoogleAuthenticator` (extends `AbstractOAuthAuthenticator`):
   - Exchanges code for access token
   - Fetches user info from Google
   - Verifies email is verified
   - Checks if user exists (by `google_id` + `email`)
   - If not exists: `OAuthRegistrationService` creates new user
   - Generates JWT token
   - **Redirects to frontend** with token: `http://localhost:3000/auth/google/callback?token=JWT`
6. Frontend stores token and uses it for API requests

**Error Handling:**
- If Google email not verified: Redirects to frontend with `?error=email+not+verify`
- Frontend receives error parameter and can display appropriate message

### Environment Configuration

Required environment variables in `.env.local`:

```env
DATABASE_URL="mysql://symfony:pass@database/symfony?serverVersion=8.0.35&charset=utf8mb4"

# JWT Authentication
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase

# OAuth2 Google
OAUTH_GOOGLE_CLIENT_ID=your_client_id
OAUTH_GOOGLE_CLIENT_SECRET=your_client_secret

# Frontend URL for invitation links
app.front_base_url=your_frontend_url
```

Generate JWT keys:
```bash
php bin/console lexik:jwt:generate-keypair
```

### Email System

Email sending handled by `MailerService` (app/src/Service/MailerService.php):
- Development: Uses Maildev (http://localhost:8081)
- Sends event invitations with 7-day expiration tokens
- Email templates in Twig format

### Invitation System

Token-based invitation flow:
1. Organizer sends invitation via `/api/events/invit/{fromUserId}/{toUserId}/{eventId}`
2. Creates `Invitation` entity with unique token and creation date
3. Email sent with frontend URL containing token
4. Receiver clicks link → frontend calls `/process/invit/{token}`
5. Backend validates token (must be < 7 days old), adds user to event, deletes invitation

Automatic cleanup via `InvitationCleanupCommand` (removes expired invitations).

### API Structure

All routes documented in `api_routes.txt`. Key patterns:

- **Admin routes**: `/api/admin/*` - User management
- **Event routes**: `/api/events/*` - CRUD + participant management
- **Gift routes**: `/api/gifts/*` - Gift list management
- **Auth routes**: `/api/login`, `/oauth/*`, `/forgot-password`
- **User routes**: `/api/user/*` - Profile management

Controllers use:
- Symfony's serializer with serialization groups
- Doctrine ORM for database operations
- Voters for authorization checks
- Validator for entity validation

### Data Fixtures

Development data in `app/src/DataFixtures/`:
- `UserFixtures.php`
- `EventFixtures.php` (typo: `EventFxitures.php`)
- `GiftFixtures.php`
- `InvitationFixture.php`

Load fixtures: `php bin/console doctrine:fixtures:load`

## Common Patterns

### Creating API Endpoints

1. Define route with `#[Route]` attribute
2. Type-hint dependencies in method signature (auto-wiring)
3. Use serialization groups for JSON responses
4. Validate with `ValidatorInterface`
5. Check authorization with voters: `$this->denyAccessUnlessGranted('edit', $event)`
6. Return `JsonResponse` with appropriate HTTP status codes

### Serialization Groups

Defined on entities with `#[Groups]` attribute:
- `eventDetail`: Full event data
- `userDetail`: Full user data
- `userList`: User listing
- `invitation`: Invitation data
- `usersInvitToEvent`: Users available to invite

### Database Operations

```bash
# Create migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate

# Create entity
php bin/console make:entity EntityName
```

## Development Notes

- The codebase is in French (comments, variable names, error messages)
- All API work happens inside the Docker PHP container
- Database changes require migrations
- Tests use transaction rollback (no database cleanup needed)
- CORS configured via `nelmio/cors-bundle`
