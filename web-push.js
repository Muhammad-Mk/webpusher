(function () {
    var q = window.webpushr.q || [];
    var _ = {
        projectId: null,
        options: {},
        uid: null,
        replaceTags: null,
        clientPushAPI: null,
        getWidgetSettings: function (projectId) {
            return new Promise(function (resolve, reject) {
                var request = new Request('https://webpushr.xyz/projects/' + projectId + '/widget_settings.json');
                fetch(request).then(function (response) {
                    response.json().then(function (json) {
                    resolve(json);
                    });
                });
            });
        },
        getApplicationServerKey: function (projectId) {
            return new Promise(function (resolve, reject) {
                var request = new Request('https://webpushr.xyz/projects/' + projectId + '/application_server_key');
                fetch(request).then(function (response) {
                    response.text().then(function (hex) {
                        resolve(_.hexToArrayBuffer(hex));
                    });
                });
            });
        },
        hexToArrayBuffer: function (hex) {
            var strBytes = hex.match(/.{2}/g);
            var bytes = new Uint8Array(strBytes.length);
            for (var i = 0; i < strBytes.length; i++) {
                bytes[i] = parseInt(strBytes[i], 16)
            }
            return bytes;
        },
        sendSubscriptionToServer: function (projectId, subscription, uid, tags, updateOnly) {
            return new Promise(function (resolve, reject) {
                var req = new XMLHttpRequest();
                req.onreadystatechange = function () {
                    if (req.readyState === 4) {
                        if (req.status === 201 || req.status === 204) {
                            resolve();
                        } else {
                            if (req.status === 403) {
                                reject('Server returned a 403 Forbidden status: this probably means that uid signature is missing or wrong, or someone tried to tamper the request.');
                            }
                            reject('Got status code ' + req.status + ' while sending subscription to server.');
                        }
                    }
                }
                req.open(updateOnly ? 'PATCH' : 'POST', 'https://webpushr.xyz/projects/' + projectId + '/subscription', true);
                req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                req.setRequestHeader('Accept', 'application/json');
                req.send(_.buildQueryString(subscription.endpoint, subscription, uid, tags));
            });
        },
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
        getSubscriptionFromServer: function (projectId, subscription) {
            return new Promise(function (resolve, reject) {
                var req = new XMLHttpRequest();
                req.onreadystatechange = function () {
                    if (req.readyState === 4) {
                        if (req.status === 200) {
                            resolve(JSON.parse(req.responseText));
                        } else {
                            reject('Got status code ' + req.status + ' while getting subscription status from server.');
                        }
                    }
                }
                req.open('GET', 'https://webpushr.xyz/projects/' + projectId + '/subscription/status?' + _.buildQueryString(subscription.endpoint), true);
                req.setRequestHeader('Accept', 'application/json');
                req.send();
            });
        },
        getSubscription: function () {
            return _.clientPushAPI.getSubscription();
        },
        subscribe: function (projectId) {
            return _.clientPushAPI.subscribe(projectId);
        },
        denied: function () {
            return _.clientPushAPI.denied();
        },
        buildQueryString: function (endpoint, subscription, uid, tags) {
            var queryString = 'endpoint=' + encodeURIComponent(endpoint);
            if (subscription && typeof subscription.toJSON === 'function') {
                queryString += '&p256dh=' + subscription.toJSON().keys.p256dh;
                queryString += '&auth=' + subscription.toJSON().keys.auth;
            }
            if (uid) {
                if (uid === true) {
                    queryString += '&uid=true';
                } else {
                    queryString += '&uid=' + encodeURIComponent(uid.value);
                    queryString += '&uid_signature=' + encodeURIComponent(uid.signature);
                }
            }
            if (tags) {
                (tags.tags || []).concat(tags.replaceTags || []).forEach(function (tag) {
                    queryString += '&tags[]=' + encodeURIComponent(tag);
                });
                if (tags.replaceTags) {
                    queryString += '&replace_tags=true';
                }
            }
            return queryString;
        },
        getUidFromOptions: function (options) {
            if (!options.uid) return null;
            if (!options.uidSignature) throw "You have set an uid but not its uidSignature.";
            return { value: options.uid, signature: options.uidSignature };
        },
        documentReady: function (callback) {
            if (document.attachEvent ? document.readyState === 'complete' : document.readyState !== 'loading') {
                callback();
            } else {
                document.addEventListener('DOMContentLoaded', callback);
            }
        },
        parseDuration: function (duration) {
            var unitsInSeconds = { second: 1, minute: 60, hour: 3600, day: 86400, week: 604800, month: 2629746, year: 31556952 };
            var valueAndUnit = duration.split(' ', 2);
            var value = parseInt(valueAndUnit[0]);
            var unit = valueAndUnit[1].slice(-1) == 's' ? valueAndUnit[1].slice(0, -1) : valueAndUnit[1];
            return value * unitsInSeconds[unit] * 1000;
        },
        shouldPromptAgain: function (promptFrequency) {
            var dismissedAt = window.localStorage.getItem('webpushrPromptDismissedAt');
            if (!dismissedAt) return true;
            return Date.now() - parseInt(dismissedAt) >= _.parseDuration(promptFrequency);
        },
        createPrompt: function (options) {
            var prompt = document.createElement('div');
            prompt.setAttribute('id', 'webpushr-prompt');
            prompt.style.all = 'initial';
            prompt.style.boxSizing = 'border-box';
            prompt.style.zIndex = 9999;
            prompt.style.position = 'fixed';
            prompt.style.bottom = 0;
            options.promptPosition == 'right' ? prompt.style.right = 0 : prompt.style.left = 0;
            prompt.style.maxWidth = '30em';
            prompt.style.padding = '1.5em';
            prompt.style.margin = options.margin;
            if (window.matchMedia('(max-width: 40em)').matches) {
                prompt.style.margin = '0';
                prompt.style.width = '100%';
                prompt.style.maxWidth = 'none';
            }
            prompt.style.border = '1px solid rgba(0, 0, 0, 0.2)';
            prompt.style.boxShadow = '5px 5px 10px rgba(0, 0, 0, 0.2)';
            prompt.style.background = 'white';
            prompt.style.textAlign = 'right';
            var promptTitle = document.createElement('h1');
            promptTitle.textContent = options.promptTitle;
            promptTitle.style.all = 'initial';
            promptTitle.style.display = 'block';
            promptTitle.style.marginBottom = '1em';
            promptTitle.style.fontSize = '1em';
            promptTitle.style.fontFamily = options.fontFamily;
            promptTitle.style.fontWeight = 'bold';
            promptTitle.style.textAlign = 'left';
            prompt.appendChild(promptTitle);
            var promptMessage = document.createElement('p');
            promptMessage.textContent = options.promptMessage;
            promptMessage.style.all = 'initial';
            promptMessage.style.display = 'block';
            promptMessage.style.fontSize = '1em';
            promptMessage.style.fontFamily = options.fontFamily;
            promptMessage.style.textAlign = 'left';
            prompt.appendChild(promptMessage);
            var promptDismiss = document.createElement('button');
            promptDismiss.setAttribute('id', 'webpushr-prompt-dismiss');
            promptDismiss.textContent = options.promptDismiss;
            promptDismiss.style.all = 'initial';
            promptDismiss.style.fontSize = '1em';
            promptDismiss.style.fontFamily = options.fontFamily;
            promptDismiss.style.textTransform = 'uppercase';
            promptDismiss.style.color = 'gray';
            promptDismiss.style.marginTop = '1.5em';
            promptDismiss.style.cursor = 'pointer';
            promptDismiss.style.textAlign = 'right';
            promptDismiss.style.border = 'none';
            promptDismiss.style.background = 'none';
            prompt.appendChild(promptDismiss);
            var promptButton = document.createElement('button');
            promptButton.setAttribute('id', 'webpushr-prompt-button');
            promptButton.textContent = options.promptButton;
            promptButton.style.all = 'initial';
            promptButton.style.fontSize = '1em';
            promptButton.style.fontFamily = options.fontFamily;
            promptButton.style.fontWeight = 'bold';
            promptButton.style.textTransform = 'uppercase';
            promptButton.style.display = 'inline-block';
            promptButton.style.backgroundColor = options.promptButtonColor;
            promptButton.style.color = 'white';
            promptButton.style.marginTop = '0.75em';
            promptButton.style.marginLeft = '1em';
            promptButton.style.padding = '0.75em 2em';
            promptButton.style.cursor = 'pointer';
            promptButton.style.textAlign = 'right';
            promptButton.style.border = 'none';
            prompt.appendChild(promptButton);
            prompt.style.display = 'none';
            document.querySelector('body').appendChild(prompt);
        },
        createBell: function (options) {
            var bell = document.createElement('button');
            bell.setAttribute('id', 'webpushr-bell');
            bell.style.zIndex = 9998;
            bell.style.position = 'fixed';
            bell.style.bottom = '0';
            options.bellPosition == 'right' ? bell.style.right = 0 : bell.style.left = 0;
            bell.style.margin = options.margin;
            bell.style.width = options.bellSize;
            bell.style.height = options.bellSize;
            bell.style.background = 'initial';
            bell.style.backgroundColor = options.bellBackgroundColor;
            bell.style.backgroundImage = 'url("https://webpushr.xyz/icons/widget-bell.png")';
            bell.style.backgroundPosition = 'center';
            bell.style.backgroundSize = '50%';
            bell.style.backgroundRepeat = 'no-repeat';
            bell.style.border = 'none';
            bell.style.borderRadius = '50%';
            bell.style.boxShadow = '0 0 30px 5px rgba(0, 0, 0, 0.2)';
            bell.style.cursor = 'pointer';
            bell.style.display = 'none';
            document.querySelector('body').appendChild(bell);
        },
        createButton: function (options) {
            var button = document.createElement('button');
            button.setAttribute('id', 'webpushr-button');
            button.style.all = 'initial';
            button.style.padding = options.buttonPadding;
            button.style.fontSize = options.buttonFontSize;
            button.style.fontFamily = options.fontFamily;
            button.style.border = 'none';
            button.style.borderRadius = '.5em';
            button.style.cursor = 'pointer';
            button.style.display = 'none';
            var buttonContainer = document.querySelector(options.buttonContainer);
            buttonContainer.style.all = 'initial';
            buttonContainer.style.display = 'inline-block';
            buttonContainer.appendChild(button);
        }
    };
    var publicMethods = {
        init: function (projectId) {
            console.log("public_key: ", projectId)
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
            console.log("call getNotificationPermissionState");
            console.log("navigator.permissions: ", navigator.permissions)
            if (navigator.permissions) {
                return navigator.permissions.query({name: 'notifications'})
                .then((result) => {
                    console.log("result navigator.permissions: ", result)
                    var local_storage_expiry = JSON.parse(localStorage.getItem("webPushPrompt"));
                    if(local_storage_expiry){
                        console.log("local_storage_expiry: ", local_storage_expiry.expiry);
                        var cuurent_date_in_milli_secs = Date.now();
                        var current_time_in_sec =  Math.round(cuurent_date_in_milli_secs/1000)

                        console.log("current_time_in_sec: ", current_time_in_sec);
                        if(current_time_in_sec >= local_storage_expiry.expiry){
                            console.log("current time greater")
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
            console.log("call getCutomPrompt")
            return fetch("http://localhost/webpushr/api/custom-prompt", {
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
            console.log("selected action: ", action)
            document.getElementById("webpushr-prompt-wrapper").remove();
            if(action == "Approve"){
                console.log("appoved for system prompt")
                publicMethods.askPermission();
            }
            else{
                var dateInMillisecs = Date.now();
                var expiry_time =  Math.round(dateInMillisecs/1000) + 24*_.custom_prompt_expiry*60*60;
                // var expiry_time =  Math.round(dateInMillisecs/1000) + 20;
                console.log("dateInMillisecs: ", dateInMillisecs)
                console.log("expiry_time: ", expiry_time)

                let dismissal_web_prompt = JSON.stringify({ 'action': action, 'expiry': expiry_time});
                localStorage.setItem("webPushPrompt", dismissal_web_prompt);
                console.log("not appoved for system prompt")
            }
        },
        askPermission: function() {
            console.log("call asking for permission")
            return new Promise(function(resolve, reject) {
                const permissionResult = Notification.requestPermission(function(result) {
                    resolve(result);
                });
                console.log("permissionResult: ", permissionResult)
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
            console.log("called subscribeUserToPush");

            return navigator.serviceWorker.register("service-worker.js")
            .then(function(registration) {
                console.log("subscribeUserToPush registration: ", registration)
                const subscribeOptions = {
                    userVisibleOnly: true,
                    applicationServerKey: publicMethods.urlBase64ToUint8Array(_.projectId)
                };
                console.log("subscribeUserToPush subscribeOptions: ", subscribeOptions)
                return registration.pushManager.subscribe(subscribeOptions);
            })
            .then(function(pushSubscription) {
                console.log('Received PushSubscription: ', pushSubscription);
                publicMethods.sendSubscriptionToBackEnd(JSON.stringify(pushSubscription));
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
            console.log("call sendSubscriptionToBackEnd: ", subscription);
            subscription = JSON.parse(subscription);
            console.log("after parse: ",subscription )
            subscription['site_key'] = _.projectId;

            console.log("obj new: ", JSON.stringify(subscription));
            return fetch("http://localhost/webpushr/api/save-subscription", {
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
        },
        close_prompt: function(){
            $("ui-dialog").addClass("display-none");
        },
        unsupported: function (callback) {
            if (!_.clientPushAPI) {
                callback();
            }
        }
    };

    window.webpushr = function () {
        console.log("window.webpushr");
        publicMethods[arguments[0]].apply(this, Array.prototype.slice.call(arguments, 1));
    };
    q.forEach(function (command) {
        console.log("command: ", command)
        window.webpushr.apply(this, command);
    });
})();