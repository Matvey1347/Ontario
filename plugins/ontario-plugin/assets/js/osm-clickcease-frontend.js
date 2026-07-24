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

function injectScript(url, className) {
  if (!url || document.querySelector('script[data-ontario-src="' + url + '"]')) {
    return;
  }

  const script = document.createElement('script');
  script.async = true;
  script.src = url;
  script.dataset.ontarioSrc = url;

  if (className) {
    script.className = className;
  }

  document.head.appendChild(script);
}

function loadClickceaseScripts() {
  const config = window.osmClickceaseLoader || {};

  if (config.monitorScriptUrl) {
    injectScript(config.monitorScriptUrl, '');
  }

  if (config.botzappingScriptUrl) {
    injectScript(config.botzappingScriptUrl, 'ct_clicktrue');
  }
}

if (document.readyState === 'complete') {
  window.setTimeout(loadClickceaseScripts, 1200);
} else {
  window.addEventListener('load', function () {
    window.setTimeout(loadClickceaseScripts, 1200);
  }, { once: true });
}
