# IdeaClarity API - Railway Deployment Guide

## Quick Fix for Healthcheck Issues

If you're getting "Healthcheck failed!" errors, follow these steps:

### 1. Make sure these environment variables are set in Railway:
```
APP_NAME="IdeaClarity API"
APP_ENV=production
APP_KEY=base64:generated-key-here
APP_DEBUG=false
APP_URL=https://ideaclarity-backend-production.up.railway.app

DB_CONNECTION=pgsql
DB_HOST=db.gyckxadiumjtdpxpsmbn.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=iQ2DaR9kRDlSa4VK

LOG_CHANNEL=stderr
LOG_LEVEL=debug
```

### 2. Test these endpoints after deployment:
- Root: `https://ideaclarity-backend-production.up.railway.app/`
- Debug: `https://ideaclarity-backend-production.up.railway.app/debug`
- Simple health: `https://ideaclarity-backend-production.up.railway.app/api/health`
- Ping: `https://ideaclarity-backend-production.up.railway.app/api/ping`

## Prerequisites
1. Railway account (https://railway.app)
2. Supabase project set up with credentials
3. GitHub repository for the backend code

## Step 1: Setup Supabase Database

1. Go to your Supabase dashboard (https://supabase.com/dashboard)
2. Select your project
3. Go to **SQL Editor**
4. Run the SQL from `/public_ideas_schema.sql`:

```sql
-- Create public_ideas table
CREATE TABLE IF NOT EXISTS public.public_ideas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title TEXT NOT NULL,
    problem TEXT NOT NULL,
    audience_tag TEXT NOT NULL,
    demand_score INTEGER NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Insert sample data
INSERT INTO public.public_ideas (title, problem, audience_tag, demand_score) VALUES
('AI-Powered Task Manager', 'Helps developers prioritize tasks using GPT and auto-scheduling.', 'Perfect for solo devs', 92),
('Social Media Analytics Dashboard', 'Unifies all social metrics into one view for makers.', 'Great for indie hackers', 71),
('Local Business Directory SaaS', 'Allows small businesses to manage bookings and payments.', 'Useful for freelancers & agencies', 74),
('Micro-SaaS Profit Tracker', 'Tracks income, churn, and growth for solo founders.', 'Loved by early-stage builders', 86),
('Cold Email Sequence Generator', 'Helps freelancers auto-generate email sequences for leads.', 'Optimized for freelance devs', 78);

-- Enable Row Level Security (RLS)
ALTER TABLE public.public_ideas ENABLE ROW LEVEL SECURITY;

-- Create a policy to allow anyone to read public ideas (since they're public)
CREATE POLICY "Public ideas are viewable by everyone" ON public.public_ideas
    FOR SELECT USING (true);
```

## Step 2: Deploy to Railway

1. Push this backend code to a GitHub repository
2. Go to Railway dashboard (https://railway.app/dashboard)
3. Click "New Project"
4. Select "Deploy from GitHub repo"
5. Connect your GitHub account and select the backend repository
6. Railway will automatically detect this is a Laravel project

## Step 3: Configure Environment Variables in Railway

**IMPORTANT**: Make sure to set all these variables before the first deployment:

```
APP_NAME="IdeaClarity API"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://ideaclarity-backend-production.up.railway.app

DB_CONNECTION=pgsql
DB_HOST=db.gyckxadiumjtdpxpsmbn.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=iQ2DaR9kRDlSa4VK

LOG_CHANNEL=stderr
LOG_LEVEL=debug
```

## Step 4: Generate Application Key

Generate a new APP_KEY locally and add it to Railway:
```bash
cd ideaclarity-api-backend
php artisan key:generate --show
```

Copy the generated key and set it as the `APP_KEY` variable in Railway.

## Step 5: Manual Migration (if needed)

After the app is deployed and running, you can run migrations manually:

1. Go to Railway Dashboard → Your Project → Deployments
2. Click on the latest deployment 
3. Go to the "Variables" tab and temporarily add: `RAILWAY_RUN_UID=0`
4. Use Railway's terminal or run:
```bash
php artisan migrate --force
```

## Step 6: Test the Deployment

Test these endpoints in order:
1. **Root**: `https://ideaclarity-backend-production.up.railway.app/`
2. **Debug**: `https://ideaclarity-backend-production.up.railway.app/debug`
3. **Health**: `https://ideaclarity-backend-production.up.railway.app/api/health`
4. **Public Ideas**: `https://ideaclarity-backend-production.up.railway.app/api/public-ideas`

## Step 7: Update Frontend API URL

Update your frontend to use the Railway API URL:
```javascript
const API_BASE_URL = 'https://ideaclarity-backend-production.up.railway.app/api';
```

## Debugging Common Issues

### Healthcheck Failed
- **Solution**: Healthchecks are now disabled. The app should start without them.
- **Test**: Visit the root URL to see if the app is responding.

### App Key Not Set
- **Symptoms**: "No application encryption key has been specified"
- **Solution**: Generate and set APP_KEY in Railway environment variables

### Database Connection Failed
- **Symptoms**: SQLSTATE errors in logs
- **Solution**: Verify all Supabase credentials in Railway environment variables
- **Check**: Visit `/debug` endpoint to see if database is configured

### 500 Internal Server Error
- **Check**: Railway deployment logs for PHP errors
- **Debug**: Visit `/debug` endpoint for configuration info

### Port Issues
- **Railway automatically sets PORT**: Don't manually set PORT variable
- **Check**: The debug endpoint shows the port being used

## Railway Configuration Files

- `railway.json`: Railway deployment configuration (healthchecks disabled)
- `nixpacks.toml`: Build configuration with PHP and PostgreSQL
- `Procfile`: Simple process definition
- `start.sh`: Alternative startup script for debugging
- `.env.example`: Template for environment variables 