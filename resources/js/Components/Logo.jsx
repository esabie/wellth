export default function Logo({ className = '', tone = 'light' }) {
    return (
        <img
            src={tone === 'dark' ? '/images/logo-dark.png' : '/images/logo.png'}
            alt="Wellth"
            width={640}
            height={560}
            className={className}
        />
    );
}
