# Browser and accessibility support

Policy target: WCAG 2.2 AA; latest two stable Chrome, Edge, and Firefox versions
plus current Firefox ESR; latest two Safari major versions on macOS and iOS.

The initial UI includes semantic landmarks, heading order, a skip link, keyboard
focus indicators, named links, mobile-safe navigation, reduced-motion support, and
non-color-only status text. Manual checks remain required: keyboard-only use,
VoiceOver with real Safari, NVDA, zoom, and reflow. Automated checks are not a
conformance certification, and Playwright WebKit is not proof of Safari support.
