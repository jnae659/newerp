<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0-rc2/dist/web/pusher.min.js"></script>
<script>
  window.Echo = new Echo({
      broadcaster: 'pusher',
      key: "{{ env('PUSHER_APP_KEY') }}",
      cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
      forceTLS: true,
      encrypted: true,
      disableStats: true,
      auth: {
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      },
      authEndpoint: '{{route("pusher.auth")}}'
  });

  // Make pusher available globally for backward compatibility
  window.pusher = window.Echo.connector.pusher;
</script>
<script src="{{ asset('js/chatify/code.js') }}"></script>
<script>
  // Messenger global variable - 0 by default
  messenger = "{{ @$id }}";
</script>
