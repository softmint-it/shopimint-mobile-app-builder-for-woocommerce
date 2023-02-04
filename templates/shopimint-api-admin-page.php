<?php include_once(plugin_dir_path(dirname(__FILE__)) . 'functions/index.php'); ?>

<!doctype html>
<html <?php language_attributes(); ?> >
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    
    <style>
      .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
      }

      @media (min-width: 768px) {
        .bd-placeholder-img-lg {
          font-size: 3.5rem;
        }
      }

      .b-example-divider {
        height: 3rem;
        background-color: rgba(0, 0, 0, .1);
        border: solid rgba(0, 0, 0, .15);
        border-width: 1px 0;
        box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
      }

      .b-example-vr {
        flex-shrink: 0;
        width: 1.5rem;
        height: 100vh;
      }

      .bi {
        vertical-align: -.125em;
        fill: currentColor;
      }

      .nav-scroller {
        position: relative;
        z-index: 2;
        height: 2.75rem;
        overflow-y: hidden;
      }

      .nav-scroller .nav {
        display: flex;
        flex-wrap: nowrap;
        padding-bottom: 1rem;
        margin-top: -1px;
        overflow-x: auto;
        text-align: center;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
      }
      
      html.wp-toolbar {
          padding-top: 0px;
          box-sizing: border-box;
          -ms-overflow-style: scrollbar;
      }
      
    </style>
</head>
<body style="background-color:#ffffff;">

<main>
  <div class="container py-4">
    <header class="pb-3 mb-4 border-bottom">
        <div class="row">
            <div class="col-4">
                <img src="https://app.shopimint.com/backend/static/logo.png" style="width:200px; max-width:90%;" />
            </div>
            <div class="col-8">
                <div style="float:right; margin-left:50px;">
                    <a href="https://app.shopimint.com" target="_blank">
                    <button class="btn btn-primary btn-md" type="button" >Visit Portal →</button>
                    </a>
                </div>
                <div style="float:right;">
                    <small style="float:right;">App Status</small>
                    <h4 style="float:right; color:green;" id="appstatustxt"> </h4>
                </div>
            </div>
        </div>
    </header>
    
    <div class="row">
        
        <div class="col-md-12" style="margin-bottom:20px; display:none;" id="letsbuildsection" >
            <div class="alert" role="alert" style="border-radius:10px; background-color:#ffffcc; border: 1px solid black;">
                It seems there is no app assosiated with your domain name.
                <br><br>
                <a href="https://app.shopimint.com/#/auth/sign_up" target="_blank">
                    <button type="button" class="btn btn-primary btn-md">Let's Build Your First App</button>
                </a>
                <br><br>
                <a href="https://help.shopimint.com/" target="_blank" style="color:black; font-size:12px;">Watch 10 min Video Guideline</a>
            </div>
        </div>
        
        <div class="col-md-6" style="margin-bottom:20px;">
            <div class="h-100 p-3 rounded-3" style="background-color:#f0f0f0; border-radius:10px;">
                <small style="float:left;">App ID</small>
                <h5 style="float:left; color:black;" id="appidtxt"></h5>
            </div>
        </div>
        <div class="col-md-6" style="margin-bottom:20px;">
            <div class="h-100 p-3 rounded-3" style="background-color:#f0f0f0; border-radius:10px; ">
                <small style="float:left;">Rest API Status</small>
                <h5 style="float:left; color:green; display:none;" id="appconnected" >Connected</h5>
                <h5 style="float:left; color:red; display:none;" id="appdisconnected">Not Configured</h5>
            </div>
        </div>
        <div class="col-md-6" style="margin-bottom:20px;">
            <div class="h-100 p-3 rounded-3" style="background-color:#f0f0f0; border-radius:10px; ">
                <small style="float:left;">Billing Status <a href="https://shopimint.com/pricing" style="padding-left:30px;"> Pricing info </a></small>
                <h5 style="float:left; color:green;" id="billingdatetxt"></h5>
            </div>
        </div>
        <div class="col-md-6" style="margin-bottom:20px;">
            <div class="h-100 p-3 rounded-3" style="background-color:#f0f0f0; border-radius:10px; ">
                <small style="float:left;">Current Plan</small>
                <h5 style="float:left; color:green;" id="currentplan"></h5>
            </div>
        </div>
    </div>

    <div class="p-5 mb-4 bg-light rounded-3" style="margin-top:10px;">
        <div class="row">
            <div class="col-md-6">
                <h1 class="display-5 fw-bold">Shopimint Mobile App Builder</h1>
                <p id="demo">
                    Turn your woocommerce store into a fully functional ios & android mobile app in minutes without any code or design skills.
                </p>
                <div class="row">
                    <div class="col-6">✔️ Unlimited Push Notifications</div>
                    <div class="col-6">✔️ Drag & Drop App Designer</div>
                    <div class="col-6">✔️ Elegant Theme Collection</div>
                    <div class="col-6">✔️ Realtime Update</div>
                    <div class="col-6">✔️ 200+ Payment Gateways</div>
                    <div class="col-6">✔️ Marketing Automation</div>
                    <div class="col-6">✔️ Augmented Reality</div>
                    <div class="col-6">✔️ Live Stories</div>
                    <div class="col-6">✔️ Super Shop</div>
                    <div class="col-6"><a href="https://shopimint.com/woocommerce/" target="_blank" style="color:black; ">& many more</a></div>
                </div>
                <a href="https://app.shopimint.com" target="_blank">
                    <button class="btn btn-primary btn-md" type="button" style="margin-top:30px;">Let's Start Building</button>
                </a>
            </div>
            <div class="col-md-6">
                <img src="https://app.shopimint.com/backend/static/app-builder.webp" style="width:100%" />
            </div>
        </div>
        
    </div>

    <div class="row align-items-md-stretch">
      <div class="col-md-4">
        <div class="h-100 p-3 rounded-3" style="background-color:#cce6ff; border-radius:10px;">
          <h5>Help Center</h5>
          <p>Learn more about design ideas , best marketing tools , industry leading app success stories for make a successful app.</p>
          <a href="https://help.shopimint.com" target="_blank"><button class="btn btn-sm btn-outline-dark" type="button">Visit Help Center</button></a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="h-100 p-3 rounded-3" style="background-color:#ffffcc; border-radius:10px;">
          <h5>Marketing Automation</h5>
          <p>Our Marketing Automation platforms can help you segment those users and set up campaigns to boost brand loyalty, user retention, and customer satisfaction. </p>
          <a href="https://shopimint.com/how-to-promote-ecommerce-mobile-app/" target="_blank"><button class="btn btn-sm btn-outline-dark" type="button">Read More</button></a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="h-100 p-3 rounded-3" style="background-color:#e6ffe6; border-radius:10px;">
          <h5>Contact Support</h5>
          <p>We are here to serve you the best solutions. Dont hesitate to contact us.</p>
          <a href="https://shopimint.com/contact/" target="_blank"><button class="btn btn-sm btn-outline-dark" type="button">Visit</button></a>
        </div>
      </div>
    </div>

    <footer class="pt-3 mt-4 text-muted border-top">
      &copy; 2023 Shopimint
    </footer>
  </div>
</main>

<script>
    getAppStatus();
    function getAppStatus(){
        
        var xhttp = new XMLHttpRequest();
        xhttp.open("POST", 'https://app.shopimint.com/backend/api/app/wooapp/wooconfig', true);
        xhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
               const appdata = JSON.parse(xhttp.responseText);
               document.getElementById("appidtxt").innerHTML = appdata.appID;
               document.getElementById("billingdatetxt").innerHTML = appdata.renew_date;
               document.getElementById("currentplan").innerHTML = appdata.current_plan.toUpperCase();
               if(appdata.connect == 1){
                   document.getElementById("appconnected").style.display = 'block';
                   document.getElementById("appdisconnected").style.display = 'none';
                   document.getElementById("appstatustxt").innerHTML = "Connected";
                   document.getElementById("letsbuildsection").style.display = 'none';
               }else{
                   document.getElementById("appconnected").style.display = 'none';
                   document.getElementById("appdisconnected").style.display = 'block';
                   document.getElementById("appstatustxt").innerHTML = "Not Connected";
                   document.getElementById("letsbuildsection").style.display = 'none';
               }
               
               
            }else{
               document.getElementById("letsbuildsection").style.display = 'block';
               document.getElementById("appstatustxt").innerHTML = "";
               document.getElementById("appstatustxt").innerHTML = "Not Connected";
            }
        };
        xhttp.send("weburl=<?php echo site_url(); ?>");
        
    }
    
</script>

</body>
</html>