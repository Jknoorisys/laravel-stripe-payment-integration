<h1>Laravel Stripe Payment Integration</h1>
<h4>Steps to clone laravel project</h4>
<ul>
    <li>Clone your project</li>
    <li>Go to the folder application using cd command on your cmd or terminal</li>
    <li>Run composer install on your cmd or terminal</li>
    <li>Copy .env.example file to .env in the root folder using command <i> cp .env.example .env </i></li>
    <li>Open your .env file and change the database name (DB_DATABASE) to whatever you have, username (DB_USERNAME) and password (DB_PASSWORD) field correspond to your configuration.</li>
    <li>Run php artisan key:generate</li>
    <li>Run php artisan migrate</li>
    <li>Run php artisan serve</li>
    <li>Go to http://localhost:8000/</li>
</ul>

<h4>Follow the steps below:</h4>
<ul>
    <li> Create New Project </li>
    <li> Install Packages for Stripe-php Using Composer </li>
    <li> Create Stripe account </li>
    <li> Configure the package </li>
    <li> Create Routes </li>
    <li> Run the app </li>
</ul>

<ol>
    <li><h5>Create a new project</h5></li>
        <p>Create a new project with the command as below.</p>
        composer create-project laravel/laravel-stripe-payment-integration
        <p>After the new project has been created, go to your project directory.</p>
        cd laravel-stripe-payment-integration
    <li><h5>Install Packages for Stripe-php Using Composer</h5></li>
        <p>Run the following command.</p>
        composer require stripe/stripe-php
    <li><h5>Create Stripe account and get API keys</h5></li>
        <p>Create a Stripe account and login to the dashboard. Navigate through the Developers -> API keys menu to get the API keys. There are two type of standard API keys named secret key and publishable key. The secret key will be masked by default which has to be revealed by clicking reveal key token control explicitly.</p>
        <img src="https://media.stripe.com/6050469652bc9a2aa6ea39ef25bd4980a723ad2a.png" alt="img" >
        <img src="https://techsolutionstuff.com/adminTheme/assets/img/stripe_payment_gateway_api_key.png" alt="img">
    <li><h5>Configure the package</h5></li>
        <p>After the package installation is complete, you open your project and add the key and secret key that you got in the .env file.</p>
        STRIPE_KEY=pk_test_xxxxxx<br>
        STRIPE_SECRET=sk_test_xxxxxx
    <li><h5>Create Routes</h5></li>
        <p>Now we need to create an application route that we will test the application test transaction on. Open the route/api.php application route file and add the new routes</p>
        <p>Create Controller</p>
        php artisan make:controller StripeController
        <p>Run database migrations</p>
        php artisan migrate
        <p>Run database seeder</p>
        php artisan db:seed --class=ProductsSeeder
    <li><h5>Run the app</h5></li>
        <p>Stripe subscription integration complete. Now we need to make a transaction. Run the Laravel server using the Artisan command below.</p>
        php artisan serve
        <p>Run APIs on postman by importing collection</p>
        https://api.postman.com/collections/18476697-0daf72f8-89a5-4a08-a382-8846c7c4c2c0?access_key=PMAT-01GVYPDJMC37HEPYGJYF7ZKED2
    <p>Thus this tutorial I provide, hopefully useful.</p>
    <p>Thanks.</p>  
</ol>
