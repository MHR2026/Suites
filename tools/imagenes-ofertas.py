# Genera las imágenes de las ofertas a partir de las fotos de la web.
import sys
from PIL import Image, ImageDraw, ImageFont, ImageFilter

web = sys.argv[1]
SERIF = f'{web}/_astro/cormorant-garamond-latin-600-normal.2CBVLo0M.woff'
SANS = '/usr/share/fonts/opentype/inter/Inter-SemiBold.otf'
BRAND_DARK = (28, 57, 66)
ACCENT = (181, 131, 90)
W, H = 1200, 800

def cover(path):
    im = Image.open(path).convert('RGB')
    s = max(W / im.width, H / im.height)
    im = im.resize((round(im.width * s), round(im.height * s)), Image.LANCZOS)
    l, t = (im.width - W) // 2, (im.height - H) // 2
    return im.crop((l, t, l + W, t + H))

def make(photo, pct, label, out):
    im = cover(f'{web}/images/{photo}.webp').convert('RGBA')
    # degradado de abajo a arriba con el color de la marca
    grad = Image.new('L', (1, H))
    for y in range(H):
        grad.putpixel((0, y), int(230 * max(0, (y - H * 0.35) / (H * 0.65)) ** 1.4))
    shade = Image.new('RGBA', (W, H), BRAND_DARK + (0,))
    shade.putalpha(grad.resize((W, H)))
    im = Image.alpha_composite(im, shade)
    d = ImageDraw.Draw(im)
    # sello circular con el descuento
    r, cx, cy = 150, W - 210, 210
    halo = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    ImageDraw.Draw(halo).ellipse((cx - r - 6, cy - r + 10, cx + r + 6, cy + r + 22), fill=(0, 0, 0, 90))
    im = Image.alpha_composite(im, halo.filter(ImageFilter.GaussianBlur(14)))
    d = ImageDraw.Draw(im)
    d.ellipse((cx - r, cy - r, cx + r, cy + r), fill=ACCENT + (255,))
    d.ellipse((cx - r + 12, cy - r + 12, cx + r - 12, cy + r - 12), outline=(255, 255, 255, 170), width=2)
    f = ImageFont.truetype(SERIF, 128)
    d.text((cx, cy - 6), f'−{pct}%', font=f, fill='white', anchor='mm', features=['lnum'])
    # etiqueta inferior
    fs = ImageFont.truetype(SANS, 34)
    d.line((64, H - 118, 154, H - 118), fill=ACCENT + (255,), width=4)
    d.text((64, H - 74), label.upper(), font=fs, fill='white', anchor='ls')
    im.convert('RGB').save(f'{web}/images/{out}.webp', 'WEBP', quality=82)

make('terraza-grande-3', 25, 'Nov 2026', 'oferta-noviembre')
make('piscina', 10, 'Early Booking 2027', 'oferta-early-booking-2027')
