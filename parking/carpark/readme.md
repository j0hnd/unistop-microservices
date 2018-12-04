# Microservice for Carpark Search
This is a microservice that focuses on carpark search, returns the details of a carpark.
e.g. carpark name, carpark service, and price, etc.

### Usage
*POST* `/api/v1/carpark/search.json`

##### Header
_Authorization: Bearer <service token>_

##### Post parameters
- Airport ID
- From Date
- End Date
- Start Time
- End Time
