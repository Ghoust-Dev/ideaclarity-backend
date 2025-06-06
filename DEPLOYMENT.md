# IdeaClarity API - Railway Deployment Guide

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

-- Create an updated_at trigger
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_public_ideas_updated_at 
    BEFORE UPDATE ON public.public_ideas 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

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

-- Create a policy to allow authenticated users to insert ideas (optional, for future admin features)
CREATE POLICY "Authenticated users can insert public ideas" ON public.public_ideas
    FOR INSERT WITH CHECK (auth.role() = 'authenticated');
```

## Step 2: Deploy to Railway

1. Push this backend code to a GitHub repository
2. Go to Railway dashboard (https://railway.app/dashboard)
3. Click "New Project"
4. Select "Deploy from GitHub repo"
5. Connect your GitHub account and select the backend repository
6. Railway will automatically detect this is a Laravel project

## Step 3: Configure Environment Variables in Railway

In your Railway project dashboard, go to **Variables** tab and add:

```
APP_NAME="IdeaClarity API"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-railway-domain.up.railway.app

DB_CONNECTION=pgsql
DB_HOST=db.gyckxadiumjtdpxpsmbn.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=iQ2DaR9kRDlSa4VK

SESSION_DRIVER=database
CACHE_STORE=database
```

## Step 4: Generate Application Key

After deployment, you can generate a new APP_KEY by running:
```bash
php artisan key:generate --show
```

Copy the generated key and update the `APP_KEY` variable in Railway.

## Step 5: Test the Deployment

Once deployed, test these endpoints:
- Health check: `https://your-railway-domain.up.railway.app/api/health`
- Public ideas: `https://your-railway-domain.up.railway.app/api/public-ideas`

## Step 6: Update Frontend API URL

Update your frontend to use the Railway API URL:
```javascript
const API_BASE_URL = 'https://your-railway-domain.up.railway.app/api';
```

## Troubleshooting

1. **Database Connection Issues**: Verify Supabase credentials in Railway environment variables
2. **CORS Issues**: Check that your frontend domain is added to the CORS middleware
3. **Migration Issues**: Railway runs migrations automatically on deploy via the start command

## Railway Configuration Files

- `railway.json`: Railway-specific deployment configuration
- `nixpacks.toml`: Build configuration with PHP and PostgreSQL
- `Procfile`: Alternative process definition
- `.env.example`: Template for environment variables 