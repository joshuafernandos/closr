import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 78 64" xmlns="http://www.w3.org/2000/svg">
            <g transform="translate(15,0) skewX(-13)">
                <rect x="0" y="44.8" width="17.7" height="19.2" />
                <rect x="23.14" y="25.6" width="17.7" height="38.4" />
                <rect x="46.28" y="6.4" width="17.7" height="57.6" />
            </g>
        </svg>
    );
}
