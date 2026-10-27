# Lifestyle car filters

## Current support and audit

The existing public listing endpoint is `GET /api/cars`. It already supports
`search`, `brand` (brand slug), `body_type`, `fuel_type`, `transmission_type`,
`sort`, `direction`, `page`, and `per_page`. Its paginated response and car
resource contract are unchanged by the lifestyle filter.

Car models currently store `body_type`, `fuel_types`, and
`transmission_types`; there are no model-level price/ownership-cost,
dimensions, passenger capacity, boot-space, safety, touring-comfort, or
performance attributes. Variant specifications and features exist but are not
a reliable, normalized source for those vehicle-level classifications.

`first-car`, `family`, `city`, `road-trips`, and `driving-fun` therefore use
explicit, editorially managed assignments. Assignments are multi-valued: a car
can appear in any combination of those categories. `electric` uses the
existing fuel classification and is not manually assigned: a car matches only
when its `fuel_types` JSON array contains exactly `electric`. This excludes
records also classified as hybrid or another fuel. This depends on the
existing Admin fuel data being accurately maintained; it does not infer from
marketing copy.

The repository is the Laravel API/Admin application and has no React/Vite
customer homepage, listing page, or frontend API client. No frontend contract
could be inspected or changed here.

## Public filter

Add the optional `use_case` query parameter to the existing `GET /api/cars`.
Accepted values are the six stable slugs:

- `first-car`
- `family`
- `city`
- `road-trips`
- `electric`
- `driving-fun`

For example:

```text
GET /api/cars?use_case=family&brand=toyota&fuel_type=petrol&sort=name&direction=asc&page=1&per_page=15
```

The filter combines with all existing listing filters. A valid category with
no matches returns the ordinary empty paginator; an unknown category receives
the existing Laravel JSON validation response. Omitting `use_case` preserves
existing results, fields, and response format.

## Admin assignment

Users with the existing `cars.create` or `cars.update` permission assign
editorial use cases in the car/model create or edit form. Multiple boxes can be
selected. The system does not calculate these five categories from model name,
body type, or descriptions. `Going electric` is read-only and derived from the
fuel data as described above.

## Further factual data

The current schema cannot support reliable automatic classifications for
first-car affordability/ownership costs, family passenger/boot/safety needs,
city dimensions/parking, road-trip comfort/luggage, or driving-fun
performance. The five editorial assignments provide an explicit classification
until authoritative attributes and criteria are added. Missing data is not
treated as evidence of suitability.
