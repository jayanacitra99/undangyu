# Fonts

## DejaVuSerif-Bold.ttf

Used by `App\Jobs\GenerateOgImageJob` to draw the couple's names onto the
1200×630 link preview image. GD's built-in bitmap font ignores `size()`, so
without a TTF the names come out at roughly 10px in the corner of a card that
is mostly seen at thumbnail size in a WhatsApp thread.

Copied from `vendor/dompdf/dompdf/lib/fonts/`, which this project already
depends on for invoice PDFs. It is the DejaVu family — a Bitstream Vera
derivative under a permissive licence that allows redistribution.

It lives here rather than being read out of `vendor/` so that a `composer
update` reshuffling dompdf's internals cannot silently break link previews.
Replace it with the brand face when there is one: the path is configurable
through `config('undangyu.og_image.font')`.
