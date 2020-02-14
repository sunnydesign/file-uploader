# File uploader

This is a simple file uploader for implementation requests Kubia API.

[ Base URL: api.kubia.com/v1 ]


## Available method

### [ GET ] `​/upload​/link`
Return link for upload file

#### Parameters:
- integer `client_id`

#### Responses:
200: Successful operation     
```
{
  "url": "string"
}
```
400: Missing parameter `client_id`


### [ POST ] ​`/upload/{hash}`
Upload file to remote host

#### Parameters:
- string `hash`

#### Responses:
200: Successful operation     
```
{
  "success": true
}
```
404: Not found


### [ GET ] ​`/upload​/{hash}`
Return link for upload file

#### Parameters:
- string `hash`

#### Responses:
200: Successful operation     

404: Not found