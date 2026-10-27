# Categories Architecture

Categories use one generic `categories` table for automotive and future content organization. `category_type` is configuration-backed (`automotive` and `content`) so consumers can add supported types without duplicating category systems.

Names are unique within a category type, while generated slugs are globally unique. Categories are soft deleted. Parent categories must be active and use the same type; self-parenting and descendant-parenting are rejected. A category with children cannot be deleted, preventing silent orphaning.

The admin list uses the shared pagination partial and the existing Laravel paginator response envelope. Public or future consumers should use the protected active lookup endpoint rather than querying inactive categories.