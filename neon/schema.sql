CREATE SCHEMA IF NOT EXISTS public;
SET search_path TO public;

CREATE EXTENSION IF NOT EXISTS "pgcrypto";

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT 'customer',
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_url TEXT,
    sort_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS menu_items (
    id SERIAL PRIMARY KEY,
    category_id INTEGER REFERENCES categories(id),
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price NUMERIC(10, 2) NOT NULL,
    image_url TEXT,
    is_vegetarian BOOLEAN DEFAULT false,
    is_vegan BOOLEAN DEFAULT false,
    is_gluten_free BOOLEAN DEFAULT false,
    allergens TEXT,
    sort_order INTEGER DEFAULT 0,
    is_available BOOLEAN DEFAULT true,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    total_amount NUMERIC(10, 2) NOT NULL,
    delivery_address TEXT,
    delivery_phone VARCHAR(20),
    delivery_notes TEXT,
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS reservations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date DATE NOT NULL,
    time TIME NOT NULL,
    guests INTEGER NOT NULL,
    special_requests TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS photos (
    id SERIAL PRIMARY KEY,
    external_id VARCHAR(255),
    title VARCHAR(200),
    description TEXT,
    original_name VARCHAR(255),
    image_url TEXT NOT NULL,
    thumbnail_url TEXT,
    category_id INTEGER REFERENCES categories(id),
    category VARCHAR(100),
    tags TEXT[],
    is_active BOOLEAN DEFAULT true,
    sort_order INTEGER DEFAULT 0,
    uploaded_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS business_info (
    id SERIAL PRIMARY KEY,
    business_name VARCHAR(255) NOT NULL DEFAULT 'Das House',
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
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
    description TEXT,
    website VARCHAR(255),
    social_media JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS gift_shop_items (
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

INSERT INTO business_info (id, business_name, email, phone, address, description)
VALUES (
    1,
    'Das House',
    'info@dashouse.com',
    '(555) 123-4567',
    'Gumpendorfer strasse 51, Vienna, Austria',
    'Welcome to Das House - your favorite local restaurant and gift shop!'
)
ON CONFLICT (id) DO NOTHING;

INSERT INTO settings (setting_key, setting_value, setting_type, description)
VALUES
    ('restaurant_name', 'Das House', 'text', 'Restaurant name'),
    ('restaurant_address', 'Gumpendorfer strasse 51, Vienna, Austria', 'text', 'Restaurant address'),
    ('restaurant_phone', '+43 677 634 238 81', 'text', 'Restaurant phone number'),
    ('restaurant_email', 'info@dashouse.at', 'text', 'Restaurant email'),
    ('opening_hours', 'Tue-Thur 10:00am - 23:30pm, Fri-Sat 10:00am - 01:00pm, Sunday 10:00am - 19:00pm, Monday Closed', 'text', 'Opening hours'),
    ('delivery_enabled', 'false', 'boolean', 'Enable delivery service'),
    ('reservations_enabled', 'true', 'boolean', 'Enable table reservations')
ON CONFLICT (setting_key) DO NOTHING;
