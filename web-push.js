(function () {
    var q = window.webpushr.q || [];
    var _ = {
        projectId: null,
        options: {},
        removeSubscriptionFromServer: function (projectId, subscription, uid, tags) {
            return new Promise(function (resolve, reject) {
                var req = new XMLHttpRequest();
                req.onreadystatechange = function () {
                    if (req.readyState === 4) {
                        if (req.status === 204) {
                            resolve();
                        } else {
                            reject('Got status code ' + req.status + ' while removing subscription from server.'); 
                        }
                    }
                };
                req.open('DELETE', 'https://webpushr.xyz/projects/' + projectId + '/subscription', true);
                req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                req.setRequestHeader('Accept', 'application/json');
                req.send(_.buildQueryString(subscription.endpoint, null, uid, tags));
            });
        },
    };
    var publicMethods = {
        init: function (projectId) {
            _.projectId = projectId;
            _.custom_prompt_expiry = null;
            _.options = {};
            if (typeof _.options.serviceWorkerPath === 'undefined') {
                _.options.serviceWorkerPath = '/service-worker.js';
            }
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
            publicMethods.registerServiceWorker();
        },
        registerServiceWorker: function(){
            return navigator.serviceWorker.register("service-worker.js")
            .then(function(registration) {
                console.log('Service worker successfully registered.');
                publicMethods.getNotificationPermissionState();
                // return registration;
            })
            .catch(function(err) {
                console.error('Unable to register service worker.', err);
            });
        },
        getNotificationPermissionState: function() {

            if (navigator.permissions) {
                return navigator.permissions.query({name: 'notifications'})
                .then((result) => {
                    var local_storage_expiry = JSON.parse(localStorage.getItem("webPushPrompt"));
                    if(local_storage_expiry){
                        var cuurent_date_in_milli_secs = Date.now();
                        var current_time_in_sec =  Math.round(cuurent_date_in_milli_secs/1000)
                        if(current_time_in_sec >= local_storage_expiry.expiry){
                            publicMethods.getCutomPrompt();
                        }
                        else{
                            console.log("current time not meet to show custom prompt again")
                        }
                    }
                    else{
                        if(result.state != "granted" && result.state != "denied"){
                            publicMethods.getCutomPrompt();
                        }
                        else {
                            // have to do work here, if cache history cleared and end points not found
                            var web_push_registered = JSON.parse(localStorage.getItem("webPushRegistered"));
                            console.log("web_push_registered: ", web_push_registered)
                            if(!web_push_registered){
                                console.log("not found web push registered")
                                
                                return navigator.serviceWorker.register("service-worker.js")
                                .then(function(registration) {
                                    console.log("registration: ", registration)
                                    registration.pushManager.getSubscription().then(function(subscription) {
                                        console.log("subscription: ", subscription)
                                        if(subscription){
                                            console.log("subscription going to reset")
                                            subscription.unsubscribe().then(function(successful) {
                                                console.log("You've successfully unsubscribed: ", successful)
                                                return publicMethods.subscribeUserToPush();
                                            }).catch(function(e) {
                                                console.log("Unsubscription failed:" , e)
                                            })
                                        }
                                        else{
                                            console.log("subscription not found, going to subscribe again");
                                            return publicMethods.subscribeUserToPush();
                                        }
                                    })
                                })
                            }
                            console.log("not need to take any action on this now");
                        }
                    }
                    // return result.state;
                });
            }
            return new Promise((resolve) => {
                console.log("resolve: ", resolve);
                resolve(Notification.permission);
            });
        },
        getCutomPrompt: function(){
            // return fetch("http://localhost/webpushr/api/custom-prompt", {
            return fetch("https://www.browserpushnotifications.com/api/custom-prompt", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({site_key: _.projectId})
            })
            .then(function(response) {
                if (!response.ok) {
                    console.log(new Error('Bad status code from server.'));
                }
                return response.json();
            })
            .then(function(responseData) {
                console.log("getCutomPrompt response: ", responseData)
                if (!responseData.status) {
                    console.log(new Error('Bad response from server.'));
                }
                else {
                    if(responseData.show_native_prompt == 1){
                        publicMethods.askPermission();
                    }
                    else{
                        prompt_wrapper=document.createElement("div");
                        prompt_wrapper.setAttribute("id", "webpushr-prompt-wrapper");
                        prompt_wrapper.innerHTML=responseData.custom_prompt;
                        document.body.appendChild(prompt_wrapper)
                        _.custom_prompt_expiry = responseData.reshow_prompt;
                        console.log("custom_prompt_expiry: ", _.custom_prompt_expiry)
                    }
                }
            });
        },
        webpushrPromptAction: function(action){
            document.getElementById("webpushr-prompt-wrapper").remove();
            if(action == "Approve"){
                publicMethods.askPermission();
            }
            else{
                var dateInMillisecs = Date.now();
                var expiry_time =  Math.round(dateInMillisecs/1000) + 24*_.custom_prompt_expiry*60*60;
                let dismissal_web_prompt = JSON.stringify({ 'action': action, 'expiry': expiry_time});
                localStorage.setItem("webPushPrompt", dismissal_web_prompt);
            }
        },
        askPermission: function() {
            console.log("call asking for permission")
            return new Promise(function(resolve, reject) {
                const permissionResult = Notification.requestPermission(function(result) {
                    resolve(result);
                });
                if (permissionResult) {
                    permissionResult.then(resolve, reject);
                }
            })
            .then(function(permissionResult) {
                console.log("then permissionResult: ", permissionResult)
                if (permissionResult == 'granted') {
                    console.log("permission granted");
                    publicMethods.subscribeUserToPush();
                }
                else if (permissionResult == 'denied') {
                    console.log("permission denied");
                    localStorage.removeItem("webPushPrompt");
                }
                else{
                    console.log("permission not granted not denied yet")
                }
            });
        },
        subscribeUserToPush: function() {
            console.log("call subscribeUserToPush")
            return navigator.serviceWorker.register("service-worker.js")
            .then(function(registration) {
                console.log("registration: ", registration)
                const subscribeOptions = {
                    userVisibleOnly: true,
                    applicationServerKey: publicMethods.urlBase64ToUint8Array(_.projectId)
                };
                return registration.pushManager.subscribe(subscribeOptions);             
            })
            .then(function(pushSubscription) {
                // console.log('Received PushSubscription: ', pushSubscription);
                publicMethods.sendSubscriptionToBackEnd(JSON.stringify(pushSubscription));

                let web_push_registered = JSON.stringify({'action': 'activated', 'webPusherEndPoint': pushSubscription.endpoint});
                localStorage.setItem("webPushRegistered", web_push_registered);
                return pushSubscription;
            });
        },
        urlBase64ToUint8Array: function(base64String) {
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
        },
        sendSubscriptionToBackEnd: function(subscription) {
            subscription = JSON.parse(subscription);
            subscription['site_key'] = _.projectId;

            // console.log("obj new: ", JSON.stringify(subscription));
            // return fetch("http://localhost/webpushr/api/save-subscription", {
            return fetch("https://www.browserpushnotifications.com/api/save-subscription", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({subscription: subscription, site_key: _.projectId})
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
    };

    window.webpushr = function () {
        // console.log("window.webpushr");
        publicMethods[arguments[0]].apply(this, Array.prototype.slice.call(arguments, 1));
    };
    q.forEach(function (command) {
        // console.log("command: ", command)
        window.webpushr.apply(this, command);
    });
})();