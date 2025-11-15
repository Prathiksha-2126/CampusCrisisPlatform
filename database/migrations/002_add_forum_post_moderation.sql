-- Migration: Add moderation support to forum posts
-- Run this in phpMyAdmin (or mysql CLI) after selecting the campus_crisis DB

USE campus_crisis;

-- Add is_approved column only if it does not exist yet
ALTER TABLE forum_posts
ADD COLUMN IF NOT EXISTS is_approved TINYINT(1) DEFAULT 0 AFTER message;

-- Mark all existing posts as approved so the community feed has content
UPDATE forum_posts
SET is_approved = 1
WHERE is_approved IS NULL;

