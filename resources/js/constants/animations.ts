export const fadeInAnimation = {
    initial: { height: 0, marginBottom: 0, opacity: 0 },
    animate: { height: 'auto', marginBottom: '1.5rem', opacity: 1 },
    exit: { height: 0, marginBottom: 0, opacity: 0 },
    transition: {
        type: 'spring',
        stiffness: 500,
        damping: 30,
        duration: 0.3,
    },
} as const;
