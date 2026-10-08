/**
 * GFit - Fundo Interativo Cinético & Biométrico (Fitness / Gym Canvas)
 * Gera partículas interativas conectadas, ondas de pulso cardíaco e reação ao cursor.
 */
(function () {
    'use strict';

    const canvas = document.getElementById('gym-canvas-bg');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width = 0;
    let height = 0;
    let animationFrameId = null;

    // Respeito a usuários com preferência de redução de movimento
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Estado do mouse / toque
    const mouse = {
        x: null,
        y: null,
        radius: 140,
        isActive: false
    };

    function resize() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    }

    window.addEventListener('resize', resize);
    resize();

    window.addEventListener('mousemove', function (e) {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
        mouse.isActive = true;
    });

    window.addEventListener('mouseleave', function () {
        mouse.x = null;
        mouse.y = null;
        mouse.isActive = false;
    });

    window.addEventListener('touchmove', function (e) {
        if (e.touches.length > 0) {
            mouse.x = e.touches[0].clientX;
            mouse.y = e.touches[0].clientY;
            mouse.isActive = true;
        }
    }, { passive: true });

    window.addEventListener('touchend', function () {
        mouse.x = null;
        mouse.y = null;
        mouse.isActive = false;
    });

    // Partículas cinéticas (representando nós musculares / dados biométricos de treino)
    const particleCount = Math.min(Math.floor((window.innerWidth * window.innerHeight) / 14000), 75);
    const particles = [];

    // Cores atléticas neon (Ciano alta intensidade, Verde eletrizante, Âmbar energia)
    const colors = [
        'rgba(6, 182, 212, ',   // Cyan / Teal performance
        'rgba(16, 185, 129, ',  // Emerald energy
        'rgba(59, 130, 246, ',  // Electric Blue
        'rgba(245, 158, 11, '   // Gold stamina
    ];

    class Particle {
        constructor() {
            this.reset();
            this.x = Math.random() * width;
            this.y = Math.random() * height;
        }

        reset() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.baseRadius = Math.random() * 2.2 + 1.2;
            this.radius = this.baseRadius;
            const speed = prefersReducedMotion ? 0.2 : (Math.random() * 0.9 + 0.4);
            const angle = Math.random() * Math.PI * 2;
            this.vx = Math.cos(angle) * speed;
            this.vy = Math.sin(angle) * speed;
            this.color = colors[Math.floor(Math.random() * colors.length)];
            this.alpha = Math.random() * 0.5 + 0.3;
            this.pulseSpeed = Math.random() * 0.03 + 0.015;
            this.pulseAngle = Math.random() * Math.PI;
        }

        update() {
            this.x += this.vx;
            this.y += this.vy;

            // Rebater nas bordas
            if (this.x < 0 || this.x > width) this.vx *= -1;
            if (this.y < 0 || this.y > height) this.vy *= -1;

            // Pulsação suave do tamanho
            this.pulseAngle += this.pulseSpeed;
            this.radius = this.baseRadius + Math.sin(this.pulseAngle) * 0.8;

            // Interatividade com o cursor (atração/repulsão dinâmica de energia)
            if (mouse.isActive && mouse.x !== null) {
                const dx = mouse.x - this.x;
                const dy = mouse.y - this.y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < mouse.radius) {
                    const force = (1 - dist / mouse.radius) * 3.5;
                    const angle = Math.atan2(dy, dx);
                    this.x -= Math.cos(angle) * force;
                    this.y -= Math.sin(angle) * force;
                    this.radius = this.baseRadius + (1 - dist / mouse.radius) * 2.5;
                }
            }
        }

        draw() {
            ctx.save();
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
            ctx.fillStyle = this.color + this.alpha + ')';
            ctx.shadowBlur = 12;
            ctx.shadowColor = this.color + '0.8)';
            ctx.fill();
            ctx.restore();
        }
    }

    for (let i = 0; i < particleCount; i++) {
        particles.push(new Particle());
    }

    // Onda de pulso cardíaco / ECG periódica
    let pulseX = 0;
    const pulseSpeed = prefersReducedMotion ? 1.5 : 3.8;

    function drawBiometricPulse() {
        ctx.save();
        const pulseY = height * 0.68;
        ctx.beginPath();
        ctx.moveTo(0, pulseY);

        const currentPulse = pulseX;
        const wavelength = 180;

        for (let x = 0; x < width; x += 15) {
            let dy = 0;
            const dist = Math.abs(x - currentPulse);
            if (dist < wavelength) {
                // Simulação de complexo QRS de eletrocardiograma (batimento cardíaco fitness)
                const norm = (x - currentPulse) / wavelength; // -1 a 1
                if (norm > -0.3 && norm < -0.1) {
                    dy = -15 * Math.sin((norm + 0.3) * Math.PI * 5);
                } else if (norm >= -0.1 && norm <= 0.1) {
                    dy = 65 * Math.sin((norm + 0.1) * Math.PI * 5); // Pico R
                } else if (norm > 0.1 && norm < 0.3) {
                    dy = -25 * Math.sin((norm - 0.1) * Math.PI * 5); // Onda S
                }
            }
            ctx.lineTo(x, pulseY - dy);
        }

        ctx.strokeStyle = 'rgba(6, 182, 212, 0.18)';
        ctx.lineWidth = 2;
        ctx.shadowBlur = 8;
        ctx.shadowColor = 'rgba(6, 182, 212, 0.4)';
        ctx.stroke();

        // Ponto de luz no pico da onda
        if (currentPulse > 0 && currentPulse < width) {
            ctx.beginPath();
            ctx.arc(currentPulse, pulseY, 3, 0, Math.PI * 2);
            ctx.fillStyle = '#06b6d4';
            ctx.shadowBlur = 14;
            ctx.shadowColor = '#06b6d4';
            ctx.fill();
        }

        ctx.restore();

        pulseX += pulseSpeed;
        if (pulseX > width + wavelength) {
            pulseX = -wavelength;
        }
    }

    // Conexões de malha entre partículas (fibras musculares / rede neural de treino)
    function connectParticles() {
        const maxDist = 120;
        for (let a = 0; a < particles.length; a++) {
            for (let b = a + 1; b < particles.length; b++) {
                const dx = particles[a].x - particles[b].x;
                const dy = particles[a].y - particles[b].y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < maxDist) {
                    const alpha = (1 - dist / maxDist) * 0.22;
                    ctx.save();
                    ctx.beginPath();
                    ctx.moveTo(particles[a].x, particles[a].y);
                    ctx.lineTo(particles[b].x, particles[b].y);
                    ctx.strokeStyle = 'rgba(6, 182, 212, ' + alpha + ')';
                    ctx.lineWidth = 1;
                    ctx.stroke();
                    ctx.restore();
                }
            }
        }
    }

    // Ondas cinéticas no clique / toque
    const clickRipples = [];
    window.addEventListener('click', function (e) {
        if (prefersReducedMotion) return;
        clickRipples.push({
            x: e.clientX,
            y: e.clientY,
            radius: 5,
            maxRadius: 100,
            alpha: 0.6
        });
    });

    function drawRipples() {
        for (let i = clickRipples.length - 1; i >= 0; i--) {
            const r = clickRipples[i];
            r.radius += 3.5;
            r.alpha -= 0.02;

            if (r.alpha <= 0 || r.radius >= r.maxRadius) {
                clickRipples.splice(i, 1);
                continue;
            }

            ctx.save();
            ctx.beginPath();
            ctx.arc(r.x, r.y, r.radius, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(16, 185, 129, ' + r.alpha + ')';
            ctx.lineWidth = 2;
            ctx.stroke();
            ctx.restore();
        }
    }

    // Loop de renderização fluida
    function animate() {
        ctx.clearRect(0, 0, width, height);

        // Fundo com degradê esportivo sutil no canvas
        const gradient = ctx.createRadialGradient(
            width * 0.5, height * 0.4, 100,
            width * 0.5, height * 0.5, Math.max(width, height) * 0.8
        );
        gradient.addColorStop(0, 'rgba(15, 23, 42, 0.4)');
        gradient.addColorStop(1, 'rgba(2, 6, 23, 0.85)');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, width, height);

        drawBiometricPulse();
        connectParticles();

        for (let i = 0; i < particles.length; i++) {
            particles[i].update();
            particles[i].draw();
        }

        drawRipples();

        animationFrameId = requestAnimationFrame(animate);
    }

    animate();
})();
