# Image and media handling

## Upload path

Admin image uploads use `App\Services\MediaService`. Controllers validate request fields and authorization; the service enforces the image byte limit, detected MIME, filename extension, decodable image content, dimensions, generated UUID filename, and database metadata. Raster formats are JPEG/JPG, PNG, GIF, WebP, and AVIF when the PHP runtime can inspect AVIF. SVG is intentionally unsupported.

The default image limit is 5 MB (`MEDIA_MAX_IMAGE_KB`); the configurable global dimension ceiling is 20,000 pixels per side and 120 megapixels (`MEDIA_MAX_DIMENSION`, `MEDIA_MAX_IMAGE_PIXELS`). There are no universal minimum dimensions or aspect ratio rules. Role-specific crop rules should be introduced only with matching UI requirements. No image conversion or thumbnail generation is currently performed; `thumbnail_url` falls back to the original image URL.

Blog gallery videos are a separate legacy feature. It accepts MP4, WebM, OGG, and MOV by server-detected MIME plus extension, with a default 50 MB limit (`MEDIA_MAX_VIDEO_KB`). Video containers are not parsed or transcoded.

## Storage and URLs

New library and module images use the `media` disk, currently rooted at `public/assets/media`, in `images/YYYY/MM/` directories. The disk URL is based on `APP_URL`; API clients should use returned URLs rather than construct filesystem paths. The disk can be moved to S3 or another configured filesystem driver without changing consumer URL construction.

Legacy `brand_media`, `car_media`, `blog_category_media`, and `public` records remain readable for existing data. Blog gallery videos remain under `public/assets/media/blogs`. New SVG uploads are rejected. AVIF acceptance depends on PHP's image inspection support; no server-side AVIF conversion is attempted.

## References and deletion

Module image fields keep legacy path columns for compatibility; Media Library selections point to the media row's stored path. Homepage, featured content, and SEO use media IDs. Replacement updates the media row and path-based references, preserving ID-based references. Deletion is rejected while a supported module references the asset. Upload cleanup removes newly stored files when the database write fails. No automatic orphan scan or destructive cleanup job is configured.

## API fields

`GET/POST /api/admin/media` returns `url`, `thumbnail_url`, `mime_type`, `extension`, `size`, `width`, `height`, and editable `alt_text`/`title`, plus identifying metadata. Other module APIs retain their established fields (`logo_url`, `thumbnail_url`, `featured_image`, or an `image: {id, url}` object). These URLs are public website URLs. Some legacy module responses also expose stored path columns; consumers should prefer the URL fields and a future API version can remove the path fields without breaking current clients.
