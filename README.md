# parseArrayImageTransferToS3
This PHP script is data transfer tool from parse.com to your AWS S3 bucket. Only in case of data is exist in array is target.

ex:[{"__type":"File","name":"tfss-d564csr2-1s9f-47eb-9887-10b5ds4cb69-image.jpg","url":"http://files.parsetfss.com/84cds3d-0a46-4649-8e8e-3e125c2e777f/tfss-d564ca22-1a9f-47eb-9887-10b55804cb69-image.jpg"},{"__type":"File","name":"tfss-e0423ht7-413d-4bbc-a585-4185fhh9c575-image.jpg","url":"http://files.parsetfss.com/84crve3d-0fs6-4649-8e8e-3e125fbe777f/tfss-e0423867-413d-4bbc-a585-4185b9f9c575-image.jpg"},{"__type":"File","name":"tfss-2ff6iufb-d4f5-4d61-bb6c-2bacx56ed84-image.jpg","url":"http://files.parsetfss.com/84cca3d-0f46-4649-8e8e-3e125c2br77f/tfss-2ff675fb-d4f5-oi61-bb6c-2ba1a556e324-image.jpg"}]

-> In case of your data is saved single data object, it should be used following tool.
https://github.com/parse-server-modules/parse-files-utils

# How to use
1. install Parse PHP SDK
2. install AWS S3 Client
3. set parse server configue value
4. set s3 configue value
5. set target class name and column name
6. set path directory
7. start script
