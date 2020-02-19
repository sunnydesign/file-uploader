# File uploader

This is a simple file uploader for implementation requests Kubia API.

[ Base URL: api.kubia.com/v1 ]

## Available method

### [ GET ] `​/upload​/link`
Return link for upload file

#### Parameters:
- integer `X-USER-UUID` (header)

#### Responses:
200: OK
```
{
  "url": "http://api.kubia.com/upload/dfb3a0ca53084eded993002903d6c1c602405ba50a130a8ba6f9c3dd291a0ec9"
}
```
403: Forbiden

400: Bad request
```json
{
  "errors": [
    {
      "code": 101,
      "message": "Missing parameter",
      "field": "X-USER-UUID"
    }
  ]
}
```

### [ POST ] ​`/upload/{hash}`
Upload file to remote host

#### Parameters:
- string `hash`

#### Responses:
200: OK
```
{
  "uuid": "162a3771-4bff-49ac-88c9-eec91ab99a99"
}
```

400: Bad request
```json
{
  "errors": [
    {
      "code": 101,
      "message": "Bad format file"
    }
  ]
}
```
```json
{
  "errors": [
    {
      "code": 102,
      "message": "File too large"
    }
  ]
}
```
```json
{
  "errors": [
    {
      "code": 998,
      "message": "File not saved"
    }
  ]
}
```
```json
{
  "errors": [
    {
      "code": 999,
      "message": "No file uploaded"
    }
  ]
}
```

404: Not found

### [ GET ] ​`/upload​/{hash}`
Return uploaded file

#### Parameters:
- string `hash`

#### Responses:
200: OK

404: Not found