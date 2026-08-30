# Prompt: Complete the Sokrat VoIP CDR Integration

You are working on the Sokrat VoIP server that exposes the CRM integration API at:

`/api/integrations/crm/v1`

Implement or verify the API contract below so SokratCRM can show reliable call history, recordings, employee attribution, and team reports. Preserve existing pairing and bearer-token authentication. Do not expose PBX credentials or filesystem paths.

## Live Compatibility Gaps Observed on 2026-08-25

The paired server is healthy and authenticated CDR/recording requests work, but its current contract differs from the target contract below:

- `GET /calls` returns `data` plus page-based `meta` and accepts `per_page`; `limit` is ignored.
- Extension statistics accept `from` and `to`; `from_date` and `to_date` are ignored.
- Extension direction accepts only `all`, `inbound`, or `outbound`; `internal` is rejected.
- The extension `status` filter is currently ignored.
- Extension statistics return `disposition_breakdown`, `daily_breakdown`, and `avg_talk_seconds` instead of the target names.
- Recording byte ranges are working correctly: a `bytes=0-99` request returned `206`, `Content-Range`, `Content-Length: 100`, `Accept-Ranges: bytes`, and `audio/wav`.

SokratCRM currently translates the supported legacy parameters and applies unsupported status/internal filters locally. Implement the target contract so filtering and aggregates remain complete when CDR volume exceeds the server's `recent_calls` window.

## Required Endpoints

### 1. Customer CDR history

`GET /calls`

Bearer scope: `calls:read`

Query parameters:

- `phone`: required; accept local and E.164 formats and normalize server-side.
- `start_date`: optional `YYYY-MM-DD`.
- `end_date`: optional `YYYY-MM-DD`, inclusive.
- `direction`: optional `inbound|outbound|internal`.
- `status`: optional `answered|missed|busy|failed|no_answer`.
- `limit`: optional integer, maximum 250.
- `cursor`: optional opaque pagination cursor.

Return:

```json
{
  "calls": [
    {
      "id": "stable-cdr-id",
      "started_at": "2026-08-25T14:20:31+03:00",
      "ended_at": "2026-08-25T14:23:04+03:00",
      "direction": "outbound",
      "customer_number": "+201001234567",
      "agent_extension": "101",
      "agent_name": "PBX display name",
      "duration_seconds": 153,
      "billsec": 148,
      "disposition": "answered",
      "recording": {
        "available": true,
        "media_id": "opaque-media-id",
        "content_type": "audio/wav",
        "duration_seconds": 148
      }
    }
  ],
  "meta": {
    "next_cursor": null,
    "has_more": false,
    "total": 1
  }
}
```

Requirements:

- `id` must never change across requests.
- `started_at` must include timezone offset.
- Direction and disposition values must use the canonical values above.
- Calls must be sorted newest first.
- Return the same CDR when the same phone is requested in equivalent local/E.164 formatting.
- The response must not include SIP secrets, PBX paths, or internal recording filenames.

### 2. Extension analytics

`GET /extensions/{extension}/stats`

Bearer scope: `stats:read`

Accept `from_date`, `to_date`, `direction`, and `status` using the same semantics as `/calls`.

Return:

```json
{
  "summary": {
    "total_calls": 42,
    "answered_calls": 35,
    "missed_calls": 7,
    "inbound_calls": 12,
    "outbound_calls": 30,
    "total_talk_seconds": 5240,
    "answer_rate_percent": 83.3
  },
  "daily": [
    {"date": "2026-08-25", "total_calls": 8, "answered_calls": 7, "talk_seconds": 930}
  ],
  "dispositions": {
    "answered": 35,
    "missed": 4,
    "busy": 1,
    "failed": 1,
    "no_answer": 1
  },
  "recent_calls": []
}
```

`recent_calls` must use the identical call schema from `/calls`, including recording metadata.

### 3. Team analytics (recommended to avoid N+1 requests)

`GET /reports/extensions`

Bearer scope: `stats:read`

Accept:

- `extensions[]`: optional list; omitted means all extensions visible to the CRM client.
- `from_date`, `to_date`, `direction`, `status`.

Return one summary row per extension plus organization totals. Use the same metric names as the extension endpoint. This endpoint must compute the report in one PBX/database query plan rather than requiring one HTTP call per CRM user.

### 4. Recording streaming

`GET /recordings/{media_id}`

Bearer scope: `recordings:read`

Requirements:

- Treat `media_id` as opaque and unguessable.
- Support `Range: bytes=...` and return valid `206`, `Content-Range`, `Content-Length`, `Accept-Ranges`, and audio `Content-Type` headers.
- Return `404` when the media ID does not exist and `403` when the integration client cannot access it.
- Stream the file; do not load the full recording into server memory.
- Log access by integration client ID, media ID, timestamp, and result without logging bearer secrets.

### 5. Extension registry

`GET /extensions`

Bearer scope: `extensions:read`

Each item must include:

```json
{
  "extension": "101",
  "name": "Sales 1",
  "online": true,
  "enabled": true
}
```

Extension numbers must be unique.

## Historical Attribution Contract

The CRM stores dated extension-to-user assignments. The VoIP server must always return the extension that handled the call and the original `started_at`; never replace old CDR extension values when PBX display names or assignments change. The CRM will resolve the extension owner for that timestamp.

## Error Contract

Use JSON errors consistently:

```json
{
  "success": false,
  "error": "Human-readable message",
  "code": "MACHINE_READABLE_CODE"
}
```

Use appropriate HTTP statuses: `400`, `401`, `403`, `404`, `422`, `429`, and `5xx`. Never return an HTTP 200 response for a failed request.

## Acceptance Tests

1. Local and E.164 versions of one number return the same stable CDR IDs.
2. Inbound, outbound, internal, answered, missed, busy, failed, and no-answer calls normalize exactly to the documented values.
3. Date boundaries are timezone-aware and inclusive.
4. Cursor pagination has no duplicates or omissions while new calls arrive.
5. Extension summary totals equal the matching filtered CDR set.
6. Team totals equal the sum of extension rows.
7. A recording can seek in Chrome and Firefox using range requests.
8. A client without `recordings:read` receives `403`.
9. Reassigning an extension does not alter historical CDR extension values.
10. Logs and API payloads never expose bearer secrets or PBX filesystem paths.

Return implementation notes, migration/schema changes, endpoint tests, and example successful responses for all four reporting endpoints.
