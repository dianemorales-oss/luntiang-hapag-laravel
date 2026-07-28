{{--
  Offline-first asset loader for Luntiang H.A.P.A.G.

  Loads Tailwind + Nunito from local files in public/assets so the app looks
  correct with NO internet connection. Nothing here touches the network.

  Files used:
    public/assets/tailwind.min.js   (Tailwind Play CDN build, pinned)
    public/assets/nunito.css        (self-hosted Nunito webfont)
    public/assets/fonts/*.woff2
--}}
<link rel="stylesheet" href="{{ asset('assets/nunito.css') }}">
<script src="{{ asset('assets/tailwind.min.js') }}"></script>
