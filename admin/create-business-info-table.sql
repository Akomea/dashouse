-- Create business_info table for storing business contact and operational information
CREATE TABLE IF NOT EXISTS business_info (
    id SERIAL PRIMARY KEY,
    business_name VARCHAR(255) NOT NULL DEFAULT 'Das House',
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    
    -- Operating hours
    monday_open TIME,
    monday_close TIME,
    tuesday_open TIME,
    tuesday_close TIME,
    wednesday_open TIME,
    wednesday_close TIME,
    thursday_open TIME,
    thursday_close TIME,
    friday_open TIME,
    friday_close TIME,
    saturday_open TIME,
    saturday_close TIME,
    sunday_open TIME,
    sunday_close TIME,
    
    -- Additional business info
    description TEXT,
    website VARCHAR(255),
    social_media JSONB,
    
    -- Metadata
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Insert default business information
INSERT INTO business_info (
    business_name, 
    email, 
    phone, 
    address, 
    description
) VALUES (
    'Das House',
    'info@dashouse.com',
    '(555) 123-4567',
    'Gumpendorfer strasse 51, Vienna, Austria',
    'Welcome to Das House - your favorite local restaurant and gift shop!'
);

-- Create function to update the updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create trigger to automatically update updated_at
CREATE TRIGGER update_business_info_updated_at 
    BEFORE UPDATE ON business_info 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();
