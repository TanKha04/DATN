-- Migration: Add evidence image columns to posts table
-- This allows storing evidence images for both recruitment and application posts

-- Add evidence_image column for storing the main evidence image path
ALTER TABLE posts ADD COLUMN evidence_image VARCHAR(255) NULL AFTER video_path;

-- Add evidence_description column for describing the evidence image
ALTER TABLE posts ADD COLUMN evidence_description TEXT NULL AFTER evidence_image;

-- Update existing posts to have default evidence descriptions based on type
UPDATE posts SET evidence_description = 
    CASE 
        WHEN type = 'recruitment' THEN 'Ảnh minh chứng tình trạng sức khỏe cần chăm sóc'
        WHEN type = 'application' THEN 'Ảnh thẻ sinh viên hoặc giấy tờ minh chứng'
        ELSE 'Ảnh minh chứng'
    END
WHERE evidence_description IS NULL;