# Ads Plugin

## API Documentation

The Ads plugin provides API endpoints to retrieve ads information.

### Base URL

The API endpoint is `/api/v1/ads`.

### Authentication

This endpoint is public and does not require authentication.

### Endpoint

#### Get Ads

```
GET or POST /api/v1/ads
```

Returns all published and non-expired ads. Can be filtered by keys.

**GET Request with filters:**

```
GET /api/v1/ads?keys[]=homepage-banner&keys[]=sidebar-banner
```

**POST Request with filters:**

```
POST /api/v1/ads
```

**POST Request Body:**

```json
{
  "keys": ["homepage-banner", "sidebar-banner"]
}
```

**Response:**

```json
{
  "error": false,
  "data": [
    {
      "key": "homepage-banner",
      "name": "Homepage Banner",
      "image": "https://example.com/images/homepage-banner.jpg",
      "tablet_image": "https://example.com/images/homepage-banner-tablet.jpg",
      "mobile_image": "https://example.com/images/homepage-banner-mobile.jpg",
      "link": "https://example.com/promo",
      "order": 1,
      "open_in_new_tab": true,
      "ads_type": "custom_ad",
      "google_adsense_slot_id": null
    },
    {
      "key": "sidebar-banner",
      "name": "Sidebar Banner",
      "image": "https://example.com/images/sidebar-banner.jpg",
      "tablet_image": "https://example.com/images/sidebar-banner-tablet.jpg",
      "mobile_image": "https://example.com/images/sidebar-banner-mobile.jpg",
      "link": "https://example.com/sale",
      "order": 2,
      "open_in_new_tab": true,
      "ads_type": "custom_ad",
      "google_adsense_slot_id": null
    }
  ],
  "message": null
}
```

### Notes

- Only published and non-expired ads are returned
- Google Adsense ads are always returned regardless of expiration date
- Ads are ordered by the `order` field
