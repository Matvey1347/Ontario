function onCheqResponse(encryptedMessage) {
  let request = new XMLHttpRequest();
  request.open('POST', osmClickcease.ajaxUrl, true);
  request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  request.send(
    'action=' +
      osmClickcease.ajaxAction +
      '&security=' +
      osmClickcease.nonce +
      '&cheq_hash=' +
      encodeURIComponent(encryptedMessage)
  );
  request.onreadystatechange = function () {
    if (request.readyState === 4) {
      if (request.status === 200) {
        let response = {};
        try {
          response = JSON.parse(request.responseText || '{}');
        } catch (error) {
          response = {};
        }
        let message = response && response.message ? response.message : {};
        performAction(message.action || '');
      }
    }
  };
}

function performAction(action) {
  if (action === 'blockuser') {
    document.querySelector('html').innerHTML = '';
    document.location.href = addGetParameters([
      { name: 'clickcease', value: 'block' },
    ]);
  } else if (action === 'clearhtml') {
    document.querySelector('html').innerHTML = '';
    document.location.href = addGetParameters([
      { name: 'clickcease', value: 'clearhtml' },
    ]);
  }
}

function addGetParameters(parameters, newUrl = window.location.href) {
  parameters.forEach(function (parameter) {
    if (newUrl.includes('?')) {
      newUrl += '&' + parameter.name + '=' + parameter.value;
    } else {
      newUrl += '?' + parameter.name + '=' + parameter.value;
    }
  });
  return newUrl;
}
