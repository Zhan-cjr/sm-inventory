/**
 * Accurately detects whether the current device is a real physical mobile/smartphone device
 * rather than a desktop PC / laptop that has been zoomed or resized.
 */
export const isRealMobileDevice = () => {
  if (typeof window === 'undefined' || typeof navigator === 'undefined') {
    return false;
  }

  const ua = (navigator.userAgent || navigator.vendor || window.opera || '').toLowerCase();

  // Explicit mobile devices User-Agents
  const isMobileUA = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(ua);

  // Hardware touch capabilities
  const hasTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

  // If it matches a known mobile UA, it's definitely mobile
  if (isMobileUA) {
    return true;
  }
	  // Fallback for touch devices: must both have touch support AND small screen width (<= 600px)
  if (hasTouch && window.innerWidth <= 600) {
    return true;
  }

  return false;
};
