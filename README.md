# IdeaClarity API Backend

A Laravel-based API backend for the IdeaClarity application, designed to connect with Supabase PostgreSQL database and integrate with external APIs like Reddit and X (Twitter).

## Features

- **PostgreSQL Integration**: Configured to work with Supabase PostgreSQL database
- **API Routes**: RESTful API endpoints with proper CORS configuration
- **Service Layer**: Organized service classes for business logic
- **External API Integration**: Ready for Reddit and X API integration using Guzzle HTTP client
- **CORS Enabled**: Configured for frontend applications (localhost:3000 and Vercel deployments)

## Requirements

- PHP 8.2 or higher
- Composer
- PostgreSQL (Supabase)

## Installation

1. **Clone the repository** (if using version control):
   ```bash
   git clone <repository-url>
   cd ideaclarity-api-backend
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Environment Configuration**:
   - Copy `.env.example` to `.env` if needed
   - Update the database configuration in `.env`:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=your-supabase-host.supabase.co
   DB_PORT=5432
   DB_DATABASE=postgres
   DB_USERNAME=postgres
   DB_PASSWORD=your-supabase-password
   ```

4. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```

5. **Run Database Migrations**:
   ```bash
   php artisan migrate
   ```

## Running the Application

### Development Server

Start the Laravel development server:

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

### Test the API

Test the ping endpoint:
```bash
curl http://localhost:8000/api/ping
```

Expected response:
```json
{
  "message": "pong"
}
```

## API Routes

- `GET /api/ping` - Test endpoint that returns "pong"
- More routes will be added as the application develops

## Service Classes

The application includes a service layer located in `app/Services/`:

- **IdeaGeneratorService**: For generating and processing ideas
- **RedditFetcherService**: For integrating with Reddit API using Guzzle HTTP client

## CORS Configuration

CORS is configured to allow requests from:
- `http://localhost:3000` (local frontend development)
- `https://localhost:3000` (local HTTPS)
- Vercel deployment domains (`*.vercel.app`)

## Database Configuration

The application is configured to work with PostgreSQL, specifically Supabase:

- **Connection**: `pgsql`
- **Port**: `5432`
- **Database**: `postgres`

Make sure to update your `.env` file with your actual Supabase credentials.

## External Dependencies

- **guzzlehttp/guzzle**: HTTP client for external API integrations
- **fruitcake/laravel-cors**: CORS handling (included with Laravel)

## Development Guidelines

1. **Service Classes**: Add business logic to service classes in `app/Services/`
2. **API Routes**: Define API routes in `routes/api.php`
3. **Database**: Use migrations for database schema changes
4. **Environment**: Keep sensitive data in `.env` file

## Next Steps

1. Configure your Supabase database credentials in `.env`
2. Set up authentication if needed (Sanctum is ready but not configured)
3. Implement Reddit API integration in `RedditFetcherService`
4. Add X (Twitter) API integration service
5. Create controllers for handling specific business logic
6. Set up proper error handling and logging

## Support

For issues and questions, please refer to the Laravel documentation or create an issue in the project repository.
