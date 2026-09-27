# Simple Slider Plugin

A plugin to create sliders for your website.

## API Documentation

The Simple Slider plugin provides an API endpoint to retrieve slider information.

### Base URL

The API endpoint is `/api/v1/simple-sliders`.

### Authentication

This endpoint is public and does not require authentication.

### Endpoint

#### Get Sliders

```
GET or POST /api/v1/simple-sliders
```

Returns all published sliders with their items. Can be filtered by keys.

**GET Request with filters:**

```
GET /api/v1/simple-sliders?keys[]=home-slider&keys[]=product-slider
```

**POST Request with filters:**

```
POST /api/v1/simple-sliders
```

**POST Request Body:**

```json
{
  "keys": ["home-slider", "product-slider"]
}
```

**Response:**

```json
{
  "error": false,
  "data": [
    {
      "id": 1,
      "name": "Home Slider",
      "key": "home-slider",
      "description": "The main slider on homepage",
      "items": [
        {
          "id": 1,
          "title": "Slide 1",
          "description": "Exclusive offer -35% off this week",
          "image": "https://example.com/images/slide-1.jpg",
          "link": "https://example.com/products",
          "order": 1,
          "metadata": [
            {
              "key": "background_color",
              "value": "#115061"
            },
            {
              "key": "is_light",
              "value": 0
            },
            {
              "key": "subtitle",
              "value": "Starting at <b>$274.00</b>"
            },
            {
              "key": "button_label",
              "value": "Shop Now"
            }
          ]
        },
        {
          "id": 2,
          "title": "Slide 2",
          "description": "Exclusive offer -10% off this week",
          "image": "https://example.com/images/slide-2.jpg",
          "link": "https://example.com/products",
          "order": 2,
          "metadata": [
            {
              "key": "background_color",
              "value": "#E3EDF6"
            },
            {
              "key": "is_light",
              "value": 1
            },
            {
              "key": "subtitle",
              "value": "Starting at <b>$999.00</b>"
            },
            {
              "key": "button_label",
              "value": "Shop Now"
            }
          ]
        }
      ]
    }
  ],
  "message": null
}
```

### Notes

- Only published sliders are returned
- Slider items are ordered by the `order` field
- Metadata for each slider item is included if available
