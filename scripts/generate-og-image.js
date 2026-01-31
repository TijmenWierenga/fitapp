import sharp from 'sharp';
import { readFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const publicDir = join(__dirname, '..', 'public');

const WIDTH = 1200;
const HEIGHT = 630;
const BACKGROUND_COLOR = '#18181b';
const LOGO_SIZE = 180;

async function generateOgImage() {
    console.log('Generating Open Graph image...');

    // Read the logo SVG
    const logoSvg = readFileSync(join(publicDir, 'logo.svg'), 'utf8');

    // Resize logo to desired size
    const logoBuffer = await sharp(Buffer.from(logoSvg))
        .resize(LOGO_SIZE, LOGO_SIZE)
        .png()
        .toBuffer();

    // Create text SVG
    const textSvg = `
        <svg width="${WIDTH}" height="${HEIGHT}" xmlns="http://www.w3.org/2000/svg">
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@600;400');
                .title {
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    font-weight: 600;
                    font-size: 72px;
                    fill: white;
                }
                .tagline {
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    font-weight: 400;
                    font-size: 32px;
                    fill: #a1a1aa;
                }
            </style>
            <text x="50%" y="400" text-anchor="middle" class="title">Traiq</text>
            <text x="50%" y="460" text-anchor="middle" class="tagline">Track your fitness journey</text>
        </svg>
    `;

    // Create the final image
    const image = await sharp({
        create: {
            width: WIDTH,
            height: HEIGHT,
            channels: 4,
            background: BACKGROUND_COLOR,
        },
    })
        .composite([
            // Add logo centered at top
            {
                input: logoBuffer,
                top: 120,
                left: Math.floor((WIDTH - LOGO_SIZE) / 2),
            },
            // Add text
            {
                input: Buffer.from(textSvg),
                top: 0,
                left: 0,
            },
        ])
        .png()
        .toFile(join(publicDir, 'og-image.png'));

    console.log('Open Graph image saved to: public/og-image.png');
    console.log(`Dimensions: ${WIDTH}x${HEIGHT}`);
}

generateOgImage().catch(console.error);
