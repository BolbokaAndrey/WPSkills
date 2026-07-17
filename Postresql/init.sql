CREATE TABLE users (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email varchar(255) NOT NULL UNIQUE,
    full_name text NOT NULL,
    active boolean NOT NULL DEFAULT true,
    roles text[] NOT NULL DEFAULT ARRAY['customer']::text[],
    profile jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status varchar(20) NOT NULL DEFAULT 'new'
        CHECK (status IN ('new', 'paid', 'cancelled')),
    amount numeric(12, 2) NOT NULL CHECK (amount >= 0),
    details jsonb NOT NULL DEFAULT '{}'::jsonb,
    created_at timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_orders_user_created_at
    ON orders (user_id, created_at DESC);

CREATE INDEX idx_users_profile_gin
    ON users USING gin (profile);

CREATE INDEX idx_orders_active
    ON orders (created_at)
    WHERE status IN ('new', 'paid');

INSERT INTO users (email, full_name, roles, profile, created_at)
VALUES
    (
        'alice@example.com',
        'Alice Ivanova',
        ARRAY['customer', 'manager'],
        '{"city": "Moscow", "age": 30}',
        '2026-01-10 10:00:00+03'
    ),
    (
        'bob@example.com',
        'Bob Petrov',
        ARRAY['customer'],
        '{"city": "Kazan", "age": 25}',
        '2026-02-15 12:30:00+03'
    );

INSERT INTO orders (user_id, status, amount, details, created_at)
VALUES
    (
        (SELECT id FROM users WHERE email = 'alice@example.com'),
        'paid',
        1490.00,
        '{"product": "Keyboard", "quantity": 1}',
        '2026-03-01 09:00:00+03'
    ),
    (
        (SELECT id FROM users WHERE email = 'alice@example.com'),
        'new',
        299.90,
        '{"product": "Mouse", "quantity": 2}',
        '2026-03-02 11:15:00+03'
    ),
    (
        (SELECT id FROM users WHERE email = 'bob@example.com'),
        'cancelled',
        799.00,
        '{"product": "Headphones", "quantity": 1}',
        '2026-03-03 17:45:00+03'
    );
