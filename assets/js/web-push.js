

document.addEventListener("DOMContentLoaded", function(event) {
    if (!('serviceWorker' in navigator)) {
        // Service Worker isn't supported on this browser, disable or hide UI.
        console.log("Service Worker isn't supported on this browser, disable or hide UI.");
        return;
    }
    if (!('PushManager' in window)) {
        // Push isn't supported on this browser, disable or hide UI.
        console.log("Push isn't supported on this browser, disable or hide UI");
        return;
    }
    registerServiceWorker();
});


document.getElementById("subscribe").addEventListener("click", function(){
    var result = getNotificationPermissionState();
    result.then(function(result) {
        console.log("Status: " + result);
        if(result != "granted"){
            askPermission();
        }
        else {
            console.log("not need to take any action on this now");
        }
    });
});


function registerServiceWorker() {
    return navigator.serviceWorker.register("assets/js/service-worker.js")
    .then(function(registration) {
        console.log('Service worker successfully registered.');
        return registration;
    })
    .catch(function(err) {
        console.error('Unable to register service worker.', err);
    });
}

function askPermission() {
    // console.log("call asking for permission")
    return new Promise(function(resolve, reject) {
        const permissionResult = Notification.requestPermission(function(result) {
            resolve(result);
        });
        // console.log("permissionResult: ", permissionResult)
        if (permissionResult) {
            permissionResult.then(resolve, reject);
        }
    })
    .then(function(permissionResult) {
        // console.log("then permissionResult: ", permissionResult)
        if (permissionResult == 'granted') {
            // console.log("permission granted");
            subscribeUserToPush();
        }
        if (permissionResult !== 'granted') {
            // throw new Error('We weren\'t granted permission.');
            console.log("We weren\'t granted permission");
        }
    });
}

function getNotificationPermissionState() {
    // console.log("call getNotificationPermissionState");
    // console.log("navigator.permissions: ", navigator.permissions)
    if (navigator.permissions) {
        return navigator.permissions.query({name: 'notifications'})
        .then((result) => {
            // console.log("result navigator.permissions: ", result)
            return result.state;
        });
    }
    return new Promise((resolve) => {
        // console.log("resolve: ", resolve);
        resolve(Notification.permission);
    });
}

function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    var rawData = window.atob(base64);
    var outputArray = new Uint8Array(rawData.length);

    for (var i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

function sendSubscriptionToBackEnd(subscription) {
    return fetch("api/save-subscription", {
        method: 'POST',
        headers: {
        'Content-Type': 'application/json'
        },
        body:subscription
    })
    .then(function(response) {
        if (!response.ok) {
            // throw new Error('Bad status code from server.');
            console.log(new Error('Bad status code from server.'));
        }
        return response.json();
    })
    .then(function(responseData) {
        console.log("subscription response: ", responseData)
        if (!responseData.status) {
            console.log(new Error('Bad response from server.'));
        }
    });
}

function subscribeUserToPush() {
    // return getSWRegistration()
    return navigator.serviceWorker.register("assets/js/service-worker.js")
    .then(function(registration) {
        // console.log("subscribeUserToPush registration: ", registration)
        const subscribeOptions = {
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(
                'BP0Vr3ZMvnOKXo4NIEH_WpFqFHoxiTiXkgMiNnkW8xwKVw2ulF6nfzgylNw0uPdqdpJ5tdryDEMCfE2eU2NPXDk'
            )
        };
        // console.log("subscribeUserToPush subscribeOptions: ", subscribeOptions)
        return registration.pushManager.subscribe(subscribeOptions);
    })
    .then(function(pushSubscription) {
        // console.log('Received PushSubscription: ', JSON.stringify(pushSubscription));
        // console.log("pushSubscription.keys: ", pushSubscription.toJSON().keys)
        const subscriptionObject = {
            endpoint: pushSubscription.endpoint,
            keys: {
                p256dh: pushSubscription.toJSON().keys.p256dh,
                auth: pushSubscription.toJSON().keys.auth
            }
        };
        // The above is the same output as:
        const subscriptionObjectToo = JSON.stringify(pushSubscription);
        sendSubscriptionToBackEnd(subscriptionObjectToo);
        return pushSubscription;
    });
}