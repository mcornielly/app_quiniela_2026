import confetti from 'canvas-confetti'

export const launchThemeChangeConfetti = () => {
    confetti({
        particleCount: 90,
        spread: 70,
        startVelocity: 42,
        origin: { x: 0.5, y: 0.4 },
        colors: ['#3b82f6', '#22c55e', '#fde047', '#ffffff', '#93c5fd'],
    })

    window.setTimeout(() => {
        confetti({
            particleCount: 55,
            spread: 95,
            startVelocity: 32,
            scalar: 0.95,
            origin: { x: 0.28, y: 0.42 },
            colors: ['#60a5fa', '#f8fafc', '#facc15'],
        })
    }, 120)

    window.setTimeout(() => {
        confetti({
            particleCount: 55,
            spread: 95,
            startVelocity: 32,
            scalar: 0.95,
            origin: { x: 0.72, y: 0.42 },
            colors: ['#60a5fa', '#f8fafc', '#34d399'],
        })
    }, 180)
}

export const launchPoolEntrySuccessConfetti = ({ backCanvas, frontCanvas }) => {
    if (!backCanvas || !frontCanvas) {
        return
    }

    const backConfettiInstance = confetti.create(backCanvas, {
        resize: true,
        useWorker: true,
    })

    const frontConfettiInstance = confetti.create(frontCanvas, {
        resize: true,
        useWorker: true,
    })

    const backBursts = [
        { particleCount: 120, spread: 72, startVelocity: 50, origin: { x: 0.5, y: 0.2 } },
        { particleCount: 70, spread: 58, startVelocity: 42, origin: { x: 0.18, y: 0.15 } },
        { particleCount: 70, spread: 58, startVelocity: 42, origin: { x: 0.82, y: 0.15 } },
        { particleCount: 48, spread: 110, startVelocity: 34, decay: 0.92, scalar: 0.9, origin: { x: 0.5, y: 0.28 } },
    ]

    const frontBursts = [
        { particleCount: 42, spread: 66, startVelocity: 38, scalar: 1.05, origin: { x: 0.32, y: 0.24 } },
        { particleCount: 42, spread: 66, startVelocity: 38, scalar: 1.05, origin: { x: 0.68, y: 0.24 } },
        { particleCount: 28, spread: 92, startVelocity: 28, decay: 0.9, scalar: 1.1, origin: { x: 0.5, y: 0.18 } },
    ]

    backBursts.forEach((burst, index) => {
        window.setTimeout(() => {
            backConfettiInstance({
                ...burst,
                ticks: 260,
                gravity: 1.02,
                colors: ['#67e8f9', '#6ee7b7', '#fde047', '#7dd3fc', '#f9a8d4', '#c4b5fd'],
            })
        }, index * 180)
    })

    frontBursts.forEach((burst, index) => {
        window.setTimeout(() => {
            frontConfettiInstance({
                ...burst,
                ticks: 220,
                gravity: 0.98,
                drift: index === 1 ? -0.15 : 0.15,
                colors: ['#ffffff', '#67e8f9', '#fde047', '#f9a8d4'],
            })
        }, 120 + (index * 220))
    })
}

export const resetConfetti = () => {
    confetti.reset()
}
