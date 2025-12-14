-- Initialize PostgreSQL for Larasuite multi-tenant architecture
-- This script runs automatically when the container is first created

-- Create extension for UUID generation
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Create extension for better text search (Spanish)
CREATE EXTENSION IF NOT EXISTS "unaccent";

-- Create central database (main app)
-- Note: The main database is created via POSTGRES_DB env var
-- This script creates additional schemas/databases for tenants dynamically

-- Grant privileges to application user
GRANT ALL PRIVILEGES ON DATABASE larasuite TO larasuite;

-- Create a function to setup new tenant databases
CREATE OR REPLACE FUNCTION create_tenant_database(tenant_name TEXT)
RETURNS VOID AS $$
BEGIN
    EXECUTE format('CREATE DATABASE %I', tenant_name);
    EXECUTE format('GRANT ALL PRIVILEGES ON DATABASE %I TO larasuite', tenant_name);
END;
$$ LANGUAGE plpgsql;

-- Log initialization
DO $$
BEGIN
    RAISE NOTICE 'Larasuite PostgreSQL initialized successfully';
END $$;
