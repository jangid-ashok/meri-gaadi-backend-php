# Media library

## Upload flow

Admin image forms and the Media Library use `App\Services\MediaService`. It inspects file content with PHP Fileinfo and `getimagesize`, enforces the shared 5 MB, 20,000-pixel-per-side, and 120 MP limits, assigns a UUID filename, stores the image on the `media` disk under `images/YYYY/MM`, and records metadata in the existing `media` table. Public URLs come from the disk configuration at `/assets/media`.

Brands, cars, blog categories, articles, profile images, and raster images in the existing blog gallery keep their existing path columns and old records. New references store the shared media path; URL helpers retain the old disk behavior for legacy paths. Media deletion checks those path references before removing an asset. Blog gallery items remain linked to their blog and are deleted from that gallery.

The reusable picker uses the centralized list and upload APIs. The selected media ID is validated for use, and its path is stored in the existing module field. The library is paginated server side, with filename, format, and upload-date filters. The older blog gallery keeps its mixed image/video UI and video upload path; its raster-image uploads now use the central service.

## Formats and image processing

- JPG/JPEG, PNG, GIF, and WebP are accepted when Fileinfo and `getimagesize` agree on valid image content.
- AVIF is accepted only when the running PHP build can inspect it and report matching AVIF content. The AVIF feature test skips when it cannot inspect the included fixture.
- SVG is rejected. Uploaded images are not converted or resized. No image-processing package is installed, so the grid uses lazy-loaded originals and no generated thumbnails are available.

## Compatibility limits

Existing image fields remain path strings, so usage detection is based on the current module columns rather than foreign-key media IDs. The checks cover Brands, Cars, Blog Categories, Articles, user profile images, and blog-gallery associations. New modules should store a selected Media path and add its field to `MediaService::usageReferences` before enabling deletion.
