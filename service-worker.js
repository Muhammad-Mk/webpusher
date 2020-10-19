function updateCampainStatus(campain_data_to_update){
  console.log("campain_data_to_update: ", campain_data_to_update)
  return fetch("http://localhost/webpushr/api/update-campain-status", {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: campain_data_to_update
  })
  .then(function(response) {
    if (!response.ok) {
      console.log("Error on updateCampainStatus: ", new Error('Bad status code from server to update campain status.'));
    }
    return response;
  })
  .then(function(response) {
    console.log("updateCampainStatus: ", response)
  });
}

var notificationClicked = 0;
self.addEventListener('push', function (event) {
  console.log("receives a push message, event: ", event);
  if (!(self.Notification && self.Notification.permission === 'granted')) {
    return;
  }

  var sendNotification = function(campaign_id, title, message, icon, link, json_data) {
    self.refreshNotifications();
    console.log("json_data: ", json_data)
    
    return self.registration.showNotification(title, {
      data: json_data,
      body: message,
      icon: icon,
      tag: link
    });
  };

  if (event.data) {
    var data = event.data.json();
    event.waitUntil(
      sendNotification(data.campaign_id, data.title, data.message, data.icon, data.link, data)
    );

    var update_campaign_status = JSON.stringify({ 'campaign_id': data.campaign_id, 'event': 0});
    updateCampainStatus(update_campaign_status);
  }

});


self.refreshNotifications = function(clientList) {
  if (clientList == undefined) {
      clients.matchAll({ type: "window" }).then(function (clientList) {
        self.refreshNotifications(clientList);
      });
  } else {
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if (client.url.search(/notifications/i) >= 0) {
          client.postMessage('reload');
        }
        client.postMessage('refreshNotifications');
      }
  }
};

self.addEventListener('notificationclick', function(event) {
  console.log('On notification click event: ', event);

  notificationClicked = 1;
  var update_campaign_status = JSON.stringify({ 'campaign_id': event.notification.data.campaign_id, 'event': 2});
  updateCampainStatus(update_campaign_status);

  // event.notification.close();

  event.waitUntil(clients.matchAll({
    type: 'window'
  }).then(function(clientList) {
    for (var i = 0; i < clientList.length; i++) {
      var client = clientList[i];
      if (client.url === '/' && 'focus' in client) {
        return client.focus();
      }
    }
    if (clients.openWindow) {
      return clients.openWindow(event.notification.tag);
    }
  }));
});

self.addEventListener('notificationclose', function(event) {
  console.log('notificationClicked: ', notificationClicked);

  if(notificationClicked == 1){
    notificationClicked = 0;
    console.log('notificationClicked set: ', notificationClicked);
  }
  else{
    console.log('On notification Close: ', event);
    var update_campaign_status = JSON.stringify({ 'campaign_id': event.notification.data.campaign_id, 'event': 1});
    updateCampainStatus(update_campaign_status);
    console.log('close notificationClicked: ', notificationClicked);    
  }
});
