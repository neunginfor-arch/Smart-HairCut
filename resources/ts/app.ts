const toggle = document.querySelector<HTMLButtonElement>('[data-theme-toggle]');
toggle?.addEventListener('click', () => { document.documentElement.classList.toggle('dark'); localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light'; });
if (localStorage.theme === 'dark') document.documentElement.classList.add('dark');

document.title = document.title.replaceAll('Smart Cut', 'SM HAIR DESIGN');
const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
const brandNodes: Text[] = [];
while (walker.nextNode()) brandNodes.push(walker.currentNode as Text);
brandNodes.forEach((node) => {
  node.nodeValue = node.nodeValue
    ?.replaceAll('SMART CUT', 'SM HAIR DESIGN')
    .replaceAll('SMARTCUT', 'SM HAIR DESIGN')
    .replaceAll('Smart Cut', 'SM HAIR DESIGN') ?? null;
});

document.querySelectorAll<HTMLElement>('[data-toast]').forEach((toast) => setTimeout(() => toast.remove(), 4000));

document.querySelectorAll<HTMLFormElement>('[data-slip-form]').forEach((form) => {
  const input = form.querySelector<HTMLInputElement>('[data-slip-input]');
  const preview = form.querySelector<HTMLImageElement>('[data-slip-preview]');
  const qrInput = form.querySelector<HTMLInputElement>('[data-slip-qr]');
  const status = form.querySelector<HTMLElement>('[data-slip-status]');
  const submit = form.querySelector<HTMLButtonElement>('[data-slip-submit]');
  const label = form.querySelector<HTMLElement>('[data-slip-label]');
  if (!input || !preview || !qrInput || !status || !submit || !label) return;

  input.addEventListener('change', async () => {
    const file = input.files?.[0];
    qrInput.value = '';
    submit.disabled = true;
    status.className = 'hidden rounded-xl px-4 py-3 text-sm font-bold';
    if (!file) return;

    label.textContent = file.name;
    const objectUrl = URL.createObjectURL(file);
    preview.src = objectUrl;
    preview.classList.remove('hidden');
    status.textContent = 'กำลังอ่าน QR Code จากสลิป...';
    status.className = 'rounded-xl bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900';

    try {
      const jsQR = (await import('jsqr')).default;
      const bitmap = await createImageBitmap(file);
      const canvas = document.createElement('canvas');
      canvas.width = bitmap.width;
      canvas.height = bitmap.height;
      const context = canvas.getContext('2d', { willReadFrequently: true });
      if (!context) throw new Error('Canvas is unavailable');
      context.drawImage(bitmap, 0, 0);
      bitmap.close();
      const image = context.getImageData(0, 0, canvas.width, canvas.height);
      const result = jsQR(image.data, image.width, image.height, { inversionAttempts: 'attemptBoth' });
      if (!result?.data) throw new Error('QR not found');

      qrInput.value = result.data;
      submit.disabled = false;
      status.textContent = '✓ พบ QR Code พร้อมตรวจสอบสลิป';
      status.className = 'rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800';
    } catch {
      status.textContent = 'ไม่พบ QR Code ในรูป กรุณาใช้สลิปต้นฉบับที่เห็น QR ชัดเจน';
      status.className = 'rounded-xl bg-red-50 px-4 py-3 text-sm font-bold text-red-700';
    } finally {
      URL.revokeObjectURL(objectUrl);
    }
  });
});

document.querySelectorAll<HTMLElement>('[data-payment-countdown]').forEach((countdown) => {
  const output = countdown.querySelector<HTMLElement>('[data-countdown-time]');
  const expiresAt = Date.parse(countdown.dataset.expiresAt ?? '');
  if (!output || Number.isNaN(expiresAt)) return;

  let hasExpired = false;
  const render = () => {
    const remaining = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;
    output.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

    if (remaining === 0 && !hasExpired) {
      hasExpired = true;
      output.textContent = 'หมดเวลา';
      document.querySelectorAll<HTMLButtonElement>('[data-slip-submit]').forEach((button) => {
        button.disabled = true;
      });
      window.setTimeout(() => {
        window.location.assign(countdown.dataset.dashboardUrl ?? '/dashboard');
      }, 800);
    }
  };

  render();
  window.setInterval(render, 1000);
});

document.querySelectorAll<HTMLElement>('[data-promotion-carousel]').forEach((carousel) => {
  const viewport = carousel.querySelector<HTMLElement>('[data-promotion-viewport]');
  const track = carousel.querySelector<HTMLElement>('[data-promotion-track]');
  const slides = Array.from(carousel.querySelectorAll<HTMLElement>('[data-promotion-slide]'));
  const previous = carousel.querySelector<HTMLButtonElement>('[data-promotion-prev]');
  const next = carousel.querySelector<HTMLButtonElement>('[data-promotion-next]');
  const counter = carousel.querySelector<HTMLElement>('[data-promotion-counter]');
  if (!viewport || !track || slides.length < 2 || !previous || !next) return;

  const makeLoopClone = (slide: HTMLElement) => {
    const clone = slide.cloneNode(true) as HTMLElement;
    clone.removeAttribute('data-promotion-slide');
    clone.setAttribute('aria-hidden', 'true');
    clone.querySelectorAll<HTMLElement>('a, button, input, select, textarea').forEach((element) => {
      element.tabIndex = -1;
    });
    return clone;
  };
  const firstClone = makeLoopClone(slides[0]);
  const lastClone = makeLoopClone(slides[slides.length - 1]);
  track.insertBefore(lastClone, slides[0]);
  track.append(firstClone);
  const loopSlides = [lastClone, ...slides, firstClone];

  const backgroundPreloads = slides.flatMap((slide) => {
    const match = window.getComputedStyle(slide).backgroundImage.match(/^url\(["']?(.*?)["']?\)$/);
    if (!match?.[1]) return [];
    const image = new Image();
    image.src = match[1];
    return [typeof image.decode === 'function'
      ? image.decode().catch(() => undefined)
      : Promise.resolve()];
  });

  let currentIndex = 0;
  let autoplayTimer: number | undefined;
  let transitionTimer: number | undefined;
  let transitionEndHandler: ((event: TransitionEvent) => void) | undefined;
  let resizeTimer: number | undefined;
  let isPaused = false;
  let isAnimating = false;
  let touchStartX: number | undefined;

  const updateCounter = () => {
    if (counter) counter.textContent = `${currentIndex + 1} / ${slides.length}`;
  };

  const offsetFor = (slide: HTMLElement) => slide.offsetLeft - track.offsetLeft;

  const setPosition = (left: number) => {
    track.style.transform = `translate3d(${-left}px, 0, 0)`;
  };

  const clearTransition = () => {
    if (transitionTimer !== undefined) {
      window.clearTimeout(transitionTimer);
      transitionTimer = undefined;
    }
    if (transitionEndHandler) {
      track.removeEventListener('transitionend', transitionEndHandler);
      transitionEndHandler = undefined;
    }
  };

  const configureTrackGeometry = () => {
    track.style.width = '100%';
    loopSlides.forEach((slide) => {
      slide.style.removeProperty('flex');
      slide.style.removeProperty('min-width');
      slide.style.removeProperty('width');
    });
    track.getBoundingClientRect();

    const widths = loopSlides.map((slide) => slide.getBoundingClientRect().width);
    const gap = Number.parseFloat(window.getComputedStyle(track).columnGap) || 0;
    loopSlides.forEach((slide, index) => {
      const width = widths[index];
      slide.style.flex = `0 0 ${width}px`;
      slide.style.minWidth = `${width}px`;
      slide.style.width = `${width}px`;
    });
    track.style.width = `${widths.reduce((total, width) => total + width, 0) + gap * (loopSlides.length - 1)}px`;
    track.getBoundingClientRect();
  };

  const jumpTo = (slide: HTMLElement) => {
    track.style.transition = 'none';
    setPosition(offsetFor(slide));
    // Commit the invisible clone-to-original reset before a later transition.
    track.getBoundingClientRect();
  };

  const animateTo = (slide: HTMLElement, onComplete?: () => void) => {
    clearTransition();
    isAnimating = true;
    track.style.willChange = 'transform';
    track.style.transition = 'transform 850ms cubic-bezier(0.22, 1, 0.36, 1)';

    const finish = () => {
      if (!isAnimating) return;
      clearTransition();
      onComplete?.();
      isAnimating = false;
      track.style.removeProperty('will-change');
    };

    transitionEndHandler = (event) => {
      if (event.target === track && event.propertyName === 'transform') finish();
    };
    track.addEventListener('transitionend', transitionEndHandler);
    setPosition(offsetFor(slide));
    transitionTimer = window.setTimeout(finish, 1000);
  };

  const goTo = (index: number) => {
    currentIndex = (index + slides.length) % slides.length;
    animateTo(slides[currentIndex]);
    updateCounter();
  };

  const goNext = () => {
    if (isAnimating) return;
    if (currentIndex < slides.length - 1) {
      goTo(currentIndex + 1);
      return;
    }

    animateTo(firstClone, () => {
      currentIndex = 0;
      jumpTo(slides[0]);
      updateCounter();
    });
  };

  const goPrevious = () => {
    if (isAnimating) return;
    if (currentIndex > 0) {
      goTo(currentIndex - 1);
      return;
    }

    animateTo(lastClone, () => {
      currentIndex = slides.length - 1;
      jumpTo(slides[currentIndex]);
      updateCounter();
    });
  };

  const stopAutoplay = () => {
    if (autoplayTimer !== undefined) {
      window.clearInterval(autoplayTimer);
      autoplayTimer = undefined;
    }
  };

  const startAutoplay = () => {
    stopAutoplay();
    if (isPaused || document.hidden) return;
    autoplayTimer = window.setInterval(goNext, 5000);
  };

  previous.addEventListener('click', () => {
    goPrevious();
    startAutoplay();
  });
  next.addEventListener('click', () => {
    goNext();
    startAutoplay();
  });

  viewport.addEventListener('pointerdown', (event) => {
    if (!isAnimating) touchStartX = event.clientX;
  }, { passive: true });
  viewport.addEventListener('pointerup', (event) => {
    if (touchStartX === undefined || isAnimating) return;
    const distance = event.clientX - touchStartX;
    touchStartX = undefined;
    if (Math.abs(distance) < 40) return;
    distance < 0 ? goNext() : goPrevious();
    startAutoplay();
  }, { passive: true });
  viewport.addEventListener('pointercancel', () => {
    touchStartX = undefined;
  }, { passive: true });

  carousel.addEventListener('pointerenter', () => {
    isPaused = true;
    stopAutoplay();
  });
  carousel.addEventListener('pointerleave', () => {
    isPaused = false;
    startAutoplay();
  });
  carousel.addEventListener('focusin', () => {
    isPaused = true;
    stopAutoplay();
  });
  carousel.addEventListener('focusout', (event) => {
    if (carousel.contains(event.relatedTarget as Node | null)) return;
    isPaused = false;
    startAutoplay();
  });
  document.addEventListener('visibilitychange', startAutoplay);
  window.addEventListener('resize', () => {
    if (resizeTimer !== undefined) window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(() => {
      clearTransition();
      isAnimating = false;
      configureTrackGeometry();
      jumpTo(slides[currentIndex]);
    }, 120);
  }, { passive: true });

  configureTrackGeometry();
  jumpTo(slides[0]);
  updateCounter();
  void Promise.all(backgroundPreloads).finally(startAutoplay);
});
