export async function onRequest(context) {
  const { request } = context;
  const url = new URL(request.url);

  // Target Railway upstream backend
  const targetUrl = new URL(url.pathname + url.search, 'https://stlawrence-junior-school-kabowa-production.up.railway.app');

  const modifiedHeaders = new Headers(request.headers);
  modifiedHeaders.set('X-Forwarded-Host', url.hostname);
  modifiedHeaders.set('X-Forwarded-Proto', url.protocol.replace(':', ''));

  const init = {
    method: request.method,
    headers: modifiedHeaders,
    redirect: 'follow',
  };

  if (request.method !== 'GET' && request.method !== 'HEAD') {
    init.body = request.body;
  }

  try {
    const response = await fetch(targetUrl.toString(), init);
    const responseHeaders = new Headers(response.headers);
    responseHeaders.set('Access-Control-Allow-Origin', '*');
    return new Response(response.body, {
      status: response.status,
      statusText: response.statusText,
      headers: responseHeaders,
    });
  } catch (err) {
    return new Response(JSON.stringify({ error: 'Backend proxy error', details: err.message }), {
      status: 502,
      headers: { 'Content-Type': 'application/json' },
    });
  }
}
