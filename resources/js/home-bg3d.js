import * as THREE from 'three';

function createRenderer() {
    const attempts = [
        { antialias: true, alpha: true, powerPreference: 'high-performance', failIfMajorPerformanceCaveat: false },
        { antialias: false, alpha: true, powerPreference: 'default', failIfMajorPerformanceCaveat: false },
        { antialias: false, alpha: true, powerPreference: 'low-power', failIfMajorPerformanceCaveat: false },
    ];
    for (const opt of attempts) {
        try {
            const renderer = new THREE.WebGLRenderer(opt);
            if (renderer.getContext()) {
                return renderer;
            }
            renderer.dispose();
        } catch (_) {
            // try next option
        }
    }
    return null;
}

export function createHomeBg3d(container, { reducedMotion = false, intensity = 1 } = {}) {
    if (!container || reducedMotion || typeof window === 'undefined') {
        return { ok: false, destroy() {} };
    }

    const renderer = createRenderer();
    if (!renderer) {
        container.classList.add('is-fallback');
        return { ok: false, destroy() {} };
    }

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(48, 1, 0.1, 100);
    camera.position.set(0, 0.2, 7.2);

    renderer.setClearColor(0x000000, 0);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.domElement.className = 'home-bg3d-canvas';
    container.classList.remove('is-fallback');
    container.classList.add('is-webgl');
    container.appendChild(renderer.domElement);

    const isCoarse = window.matchMedia('(pointer: coarse)').matches;
    const particleCount = Math.floor((isCoarse ? 650 : 1400) * intensity);

    const positions = new Float32Array(particleCount * 3);
    const phases = new Float32Array(particleCount);
    for (let i = 0; i < particleCount; i += 1) {
        const i3 = i * 3;
        positions[i3] = (Math.random() - 0.5) * 16;
        positions[i3 + 1] = (Math.random() - 0.5) * 9;
        positions[i3 + 2] = (Math.random() - 0.5) * 10;
        phases[i] = Math.random() * Math.PI * 2;
    }

    const particleGeo = new THREE.BufferGeometry();
    particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const particleMat = new THREE.PointsMaterial({
        color: new THREE.Color('#ff6d38'),
        size: isCoarse ? 0.07 : 0.055,
        transparent: true,
        opacity: 0.9,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
        sizeAttenuation: true,
    });
    const particles = new THREE.Points(particleGeo, particleMat);
    scene.add(particles);

    const glowGeo = new THREE.BufferGeometry();
    const glowCount = Math.floor(particleCount * 0.18);
    const glowPos = new Float32Array(glowCount * 3);
    for (let i = 0; i < glowCount; i += 1) {
        glowPos[i * 3] = (Math.random() - 0.5) * 12;
        glowPos[i * 3 + 1] = (Math.random() - 0.5) * 7;
        glowPos[i * 3 + 2] = (Math.random() - 0.5) * 8;
    }
    glowGeo.setAttribute('position', new THREE.BufferAttribute(glowPos, 3));
    const glowMat = new THREE.PointsMaterial({
        color: new THREE.Color('#ffc4a3'),
        size: 0.16,
        transparent: true,
        opacity: 0.45,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
    });
    const glow = new THREE.Points(glowGeo, glowMat);
    scene.add(glow);

    const mesh = new THREE.Mesh(
        new THREE.IcosahedronGeometry(2.1, 1),
        new THREE.MeshBasicMaterial({
            color: new THREE.Color('#ff6d38'),
            wireframe: true,
            transparent: true,
            opacity: 0.55,
        }),
    );
    mesh.position.set(1.8, 0, -0.4);
    scene.add(mesh);

    const ring = new THREE.Mesh(
        new THREE.TorusGeometry(2.7, 0.035, 16, 140),
        new THREE.MeshBasicMaterial({
            color: new THREE.Color('#ff814f'),
            transparent: true,
            opacity: 0.5,
        }),
    );
    ring.rotation.x = Math.PI * 0.55;
    ring.position.set(-1.4, 0.15, -0.8);
    scene.add(ring);

    const orb = new THREE.Mesh(
        new THREE.SphereGeometry(0.35, 24, 24),
        new THREE.MeshBasicMaterial({
            color: new THREE.Color('#ff6d38'),
            transparent: true,
            opacity: 0.35,
        }),
    );
    orb.position.set(-2.2, 1.1, 0.6);
    scene.add(orb);

    const pointer = { x: 0, y: 0, tx: 0, ty: 0 };
    const onPointer = (event) => {
        if (isCoarse) {
            return;
        }
        const rect = container.getBoundingClientRect();
        const w = Math.max(rect.width, 1);
        const h = Math.max(rect.height, 1);
        pointer.tx = ((event.clientX - rect.left) / w) * 2 - 1;
        pointer.ty = ((event.clientY - rect.top) / h) * 2 - 1;
    };
    window.addEventListener('pointermove', onPointer, { passive: true });

    let frame = 0;
    let running = true;
    let resizeObserver = null;

    const resize = () => {
        const width = container.clientWidth || window.innerWidth;
        const height = container.clientHeight || window.innerHeight;
        if (width < 2 || height < 2) {
            return;
        }
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
    };
    resize();
    window.addEventListener('resize', resize);
    if (typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(resize);
        resizeObserver.observe(container);
    }

    const visibility = () => {
        running = document.visibilityState !== 'hidden';
    };
    document.addEventListener('visibilitychange', visibility);

    const clock = new THREE.Clock();
    const tick = () => {
        frame = window.requestAnimationFrame(tick);
        if (!running) {
            return;
        }
        const t = clock.getElapsedTime();
        pointer.x += (pointer.tx - pointer.x) * 0.06;
        pointer.y += (pointer.ty - pointer.y) * 0.06;

        particles.rotation.y = t * 0.05 + pointer.x * 0.2;
        particles.rotation.x = t * 0.015 + pointer.y * 0.1;
        glow.rotation.y = -t * 0.03;

        const pos = particleGeo.attributes.position.array;
        for (let i = 0; i < particleCount; i += 1) {
            pos[i * 3 + 1] += Math.sin(t * 0.55 + phases[i]) * 0.0022;
        }
        particleGeo.attributes.position.needsUpdate = true;

        mesh.rotation.x = t * 0.22;
        mesh.rotation.y = t * 0.28 + pointer.x * 0.35;
        mesh.position.x = 1.8 + pointer.x * 0.45;
        mesh.position.y = pointer.y * 0.25;

        ring.rotation.z = t * 0.16;
        ring.rotation.y = t * 0.09 + pointer.x * 0.15;
        orb.position.y = 1.1 + Math.sin(t * 1.2) * 0.25;
        orb.position.x = -2.2 + pointer.x * 0.2;

        camera.position.x = pointer.x * 0.45;
        camera.position.y = 0.2 + pointer.y * 0.25;
        camera.lookAt(0, 0, 0);

        renderer.render(scene, camera);
    };
    tick();

    return {
        ok: true,
        destroy() {
            running = false;
            window.cancelAnimationFrame(frame);
            window.removeEventListener('pointermove', onPointer);
            window.removeEventListener('resize', resize);
            document.removeEventListener('visibilitychange', visibility);
            resizeObserver?.disconnect();
            particleGeo.dispose();
            particleMat.dispose();
            glowGeo.dispose();
            glowMat.dispose();
            mesh.geometry.dispose();
            mesh.material.dispose();
            ring.geometry.dispose();
            ring.material.dispose();
            orb.geometry.dispose();
            orb.material.dispose();
            renderer.dispose();
            renderer.domElement.remove();
        },
    };
}
