# Hasher
Is an application that will manage which apps are authorized to endpoints of the given system. This will generate tokens
and toggle services which are active.

### Usage
*POST* `/api/v1/get.json`

##### Parameters
- appname
- service

*POST* `/api/v1/refresh.json`

#### Header
- Service Token
_Authorization: Bearer <service token>_

#### Parameters
- appname
- service

*POST* `/api/v1/vaidate.json`

#### Header
- Service Token
_Authorization: Bearer <service token>_

#### Parameters
- appname
- service
