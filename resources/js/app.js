/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';
import { createApp } from 'vue';
import '../css/app.css';
import '@fortawesome/fontawesome-free/css/all.css';




/**
 * Next, we will create a fresh Vue application instance. You may then begin
 * registering components with the application instance so they are ready
 * to use in your application's views. An example is included for you.
 */

const app = createApp({});

import ExampleComponent from './components/ExampleComponent.vue';
app.component('example-component', ExampleComponent);
import LoginComponent from './components/Login.vue';
app.component('login-component', LoginComponent);
import DashboardComponent from './components/Dashboard.vue';
app.component('dashboard', DashboardComponent);
import MembershipPayment from "@/components/MembershipPayment.vue";
app.component('membership-payment', MembershipPayment);
import Estacion from './components/Estacion.vue';
app.component('estacion', Estacion);
import  UserManagement from './components/UserManagement.vue';
app.component('user-management', UserManagement);
import Routes from "@/components/Routes.vue";
app.component('Routes', Routes);
import bicicletas from "@/components/bicicletas.vue";
import Bicicletas from "@/components/bicicletas.vue";
app.component('bicicletas', Bicicletas);



/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// Object.entries(import.meta.glob('./**/*.vue', { eager: true })).forEach(([path, definition]) => {
//     app.component(path.split('/').pop().replace(/\.\w+$/, ''), definition.default);
// });

/**
 * Finally, we will attach the application instance to a HTML element with
 * an "id" attribute of "app". This element is included with the "auth"
 * scaffolding. Otherwise, you will need to add an element yourself.
 */

app.mount('#app');
