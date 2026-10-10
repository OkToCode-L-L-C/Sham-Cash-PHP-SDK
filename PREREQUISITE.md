# Prerequisites and Service Registration

Before integrating your application with ShamCash, you must complete the **Service Creation** process and obtain the required API credentials.

## 1. Register a Commercial Account

You must have a valid, active, and fully verified ShamCash commercial account before creating your service.

If you don't already have one, you can register online:

[**Register a ShamCash Commercial Account**](https://www.shamcash.sy/ar/createAccount/commercial)

## 2. Prepare the Required Information

During the service creation process, you will need to provide the following information:

- **Verified Commercial Account:** A valid, active, and fully verified ShamCash commercial account.
- **Logo URL:** A publicly accessible HTTP or HTTPS URL pointing to your application's logo. ShamCash uses this logo to identify your application to users.
- **Application Name:** The name displayed to users when they make payments through ShamCash.

## 3. Receive Your API Credentials

Once your service has been created, ShamCash will provide the following integration credentials:

- **`agentKey`:** Identifies your application to ShamCash.
- **`secretKey`:** A Base64-encoded AES key representing 32 bytes of secret key material, used to encrypt and decrypt API data.
- **`baseUrl`:** The root URL used to access the ShamCash API endpoints.

## 4. Secure Your Credentials

**Security note:** Keep your `secretKey` confidential and store it securely on your server, preferably in environment variables or a secrets manager.

- Never expose your `secretKey` in frontend code or client-side applications.
- Never commit API credentials to a public repository.
- Store credentials in environment variables or a secrets manager.
- Keep your credentials separate for development, testing, and production environments whenever applicable.

## 5. Continue with the SDK Setup

After completing service registration and obtaining your credentials, continue to the [SDK installation and configuration guide](README.md#install) to start integrating ShamCash payments.
