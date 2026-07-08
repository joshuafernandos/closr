import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="CLOSR">
            <path d="M 81.9 34 A 34 34 0 1 0 81.9 86" fill="none" stroke="currentColor" strokeWidth="18" strokeLinecap="round" />
            <circle cx="78" cy="60" r="10" fill="#B8F000" />
        </svg>
    );
}
