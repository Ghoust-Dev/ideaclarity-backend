-- Create competitor_results table for storing AI-generated competitor analysis
CREATE TABLE IF NOT EXISTS competitor_results (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    idea_id INTEGER NOT NULL,
    user_id UUID NOT NULL,
    competitors_data JSONB NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_competitor_results_idea_id ON competitor_results(idea_id);
CREATE INDEX IF NOT EXISTS idx_competitor_results_user_id ON competitor_results(user_id);
CREATE INDEX IF NOT EXISTS idx_competitor_results_created_at ON competitor_results(created_at);

-- Add foreign key constraint if public_ideas table exists
-- (This will fail gracefully if the table doesn't exist)
DO $$ 
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_name = 'public_ideas' 
        AND table_schema = 'public'
    ) THEN
        ALTER TABLE competitor_results 
        ADD CONSTRAINT fk_competitor_results_idea_id 
        FOREIGN KEY (idea_id) REFERENCES public_ideas(id) ON DELETE CASCADE;
    END IF;
EXCEPTION
    WHEN others THEN
        -- Ignore error if constraint already exists
        NULL;
END $$; 