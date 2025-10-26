<!-- favicon -->
<link rel="shortcut icon" href="{{ get_fav($basic_settings) }}" type="image/x-icon">
<!-- fontawesome css link -->
   <!-- fontawesome css link -->
   <link rel="stylesheet" href="{{ asset('public/frontend/') }}/css/fontawesome-all.min.css">
   <!-- line-awesome-icon css -->
   <link rel="stylesheet" href="{{ asset('public/frontend/') }}/css/line-awesome.min.css">
   <!-- bootstrap css link -->
   <link rel="stylesheet" href="{{ asset('public/frontend/') }}/css/bootstrap.min.css">
   <!-- swipper css link -->
   <link rel="stylesheet" href="{{ asset('public/frontend/') }}/css/swiper.min.css">
   <!-- animate css link -->
   <link rel="stylesheet" href="{{ asset('public/frontend/') }}/css/animate.css">

   <link rel="stylesheet" href="{{ asset('public/backend/css/select2.min.css') }}">
   <!-- nice select css -->
    <link rel="stylesheet" href="{{ asset('public/frontend/css/nice-select.css') }}">
      <!-- odometer css link -->
      <link rel="stylesheet" href="{{ asset('public/frontend/') }}/css/odometer.css">
      <!-- main style css link -->
   <link rel="stylesheet" href="{{ asset('public/frontend/') }}/css/prettify.css">
   <link rel="stylesheet" href="{{ asset('public/frontend/') }}/css/style.css">
   @php
   $color = @$basic_settings->base_color ?? '#4A8FCA';
@endphp

<style>
   :root {
       --primary-color: {{$color}};
   }

   .developer-main-wrapper .ordered-list {
       list-style: decimal;
       padding-left: 1.5rem;
       line-height: 1.7;
   }

   .developer-main-wrapper .unordered-list {
       list-style: disc;
       padding-left: 1.5rem;
       line-height: 1.7;
   }

   .code-sample-grid {
       display: grid;
       gap: 1.5rem;
   }

   @media (min-width: 992px) {
       .code-sample-grid {
           grid-template-columns: repeat(3, minmax(0, 1fr));
       }
   }

   .code-sample-grid pre {
       background: #0f172a;
       color: #f8fafc;
       padding: 1.25rem;
       border-radius: 12px;
       overflow-x: auto;
   }

</style>
