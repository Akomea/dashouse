-- Create gift_shop_items table in Supabase
-- Run this SQL in your Supabase SQL editor

CREATE TABLE public.gift_shop_items (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT '',
    image_url TEXT DEFAULT '',
    filename VARCHAR(255) DEFAULT '',
    original_name VARCHAR(255) DEFAULT '',
    active BOOLEAN DEFAULT true,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create updated_at trigger
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_gift_shop_items_updated_at 
    BEFORE UPDATE ON public.gift_shop_items 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

-- Insert existing data from JSON file
INSERT INTO public.gift_shop_items (name, description, image_url, filename, original_name, active, sort_order, created_at) VALUES
('tshirt', '', 'https://lvatvujwtyqwdsbqxjvm.supabase.co/storage/v1/object/public/dashouse-bucket/689930d7191a4_1754869975.png', '689930d7191a4_1754869975.png', 'tshirt.png', true, 0, '2025-08-10 23:52:56+00'),
('1', '', 'https://lvatvujwtyqwdsbqxjvm.supabase.co/storage/v1/object/public/dashouse-bucket/689940f0577cf_1754874096.jpg', '689940f0577cf_1754874096.jpg', '1.jpg', true, 1, '2025-08-11 01:01:36+00');

-- Enable Row Level Security (RLS)
ALTER TABLE public.gift_shop_items ENABLE ROW LEVEL SECURITY;

-- Create policy to allow anyone to read active gift shop items
CREATE POLICY "Allow anonymous read access to active gift shop items" ON public.gift_shop_items
    FOR SELECT USING (active = true);

-- Create policy to allow authenticated users to manage gift shop items (for admin)
CREATE POLICY "Allow authenticated users full access to gift shop items" ON public.gift_shop_items
    FOR ALL USING (auth.role() = 'authenticated');

-- Create policy to allow service role full access (for server-side operations)
CREATE POLICY "Allow service role full access to gift shop items" ON public.gift_shop_items
    FOR ALL USING (auth.role() = 'service_role');

-- Grant permissions
GRANT SELECT ON public.gift_shop_items TO anon;
GRANT ALL ON public.gift_shop_items TO authenticated;
GRANT ALL ON public.gift_shop_items TO service_role;
GRANT USAGE ON SEQUENCE gift_shop_items_id_seq TO authenticated;
GRANT USAGE ON SEQUENCE gift_shop_items_id_seq TO service_role;
