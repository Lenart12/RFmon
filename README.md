<h1>
    <img src="./docs/rfmon-title.png" height="100" alt="RFmon">
</h1>

![preview](./docs/preview.png)

RFMON is a tool that captures and processes FM radio signals. It saves these recordings and organizes them into groups, making it easier to track and follow conversations. This data is then displayed through a web application, providing a user-friendly interface to review the captured audio.

> [!CAUTION]
> **Disclaimer:** Recording conversations or communications without consent may be illegal in your jurisdiction. It is the user's responsibility to ensure that they have obtained all necessary permissions from individuals being recorded. The developer(s) of RFMON are not liable for any misuse of this software.

## Dependencies

- [PHP >= 8](https://www.php.net/releases/8.0/)
- [RTLSDR_Airband](https://github.com/charlie-foxtrot/RTLSDR-Airband)

## Installing

To install RFMON, follow these steps:

0. **(Clone this repository)**

1. **Install dependecies**:
    - Use another guide to install a webserver that supports PHP (Apache, NGINX, etc).
    - Compile and Install RTLSDR_Airband
        - Begin by compiling and installing the RTLSDR_Airband software with narrow FM support. Ensure that you enable the narrow FM option during the compilation process (`cmake -DNFM=ON ../`).
    - Install apt dependecies
        ```sh
        sudo apt install inotify-tools
        ```

2. **Configure RFmon**:
    - Create RFmon configuration file by copying and renaming `RFmon/html/conf.example.php` to `RFmon/html/conf.php`. Then edit the newly created file to your wanted settings. All settings are documented inside the file. 

3. **Install RFmon service**:
    - Run the service installation script with `sudo service/install_service.sh` and follow the guided install.

4. **Deploy HTML Folder**:
    - Add a symbolic link for the HTML folder to your deployment directory or configure site settings to point to the html folder. This allows the web application to access the necessary HTML files. Example:
        ```sh
        sudo ln -s /path/to/RFmon/html /var/www/html/rfmon
        ```
5. **(Optional) Configure password authentication**:
    - If needed, password authentication can be enabled to prevent people who do not know the password from accessing
      the app. To do this, uncomment the `$PASSWORD` setting in `RFmon/html/conf.php` file which
      sets the before mentioned variable to your wanted password.

6. **(Optional) Configure notifications**:
    - To enable notifications, you need to configure the notification settings in `conf.php`. Adjust the configuration according to your notification preferences.

    - Note: **Configure Mail Settings**
        - Ensure that your system is configured to send mail. This is necessary for the notification feature to work correctly. You can use tools like `sendmail`, `postfix`, or any other mail transfer agent (MTA) of your choice. Configure the MTA according to your system's requirements and ensure it is running properly.

    - After enabling notifications, restart the `rfmon.service`.

7. **(Optional) Configure transcriptions**

    - First install dependencies
        ```sh
        sudo apt curl jq sox
        ```

    - To enable transcriptions, you need to enable them `conf.php`.

    - Create an API token for [Hugging Face](https://huggingface.co/) that has '`Make calls to the serverless Inference API`' enabled and set it in `conf.php`. (Settings > Access Tokens > Create new token).
    > Users on Hugging Face get 1,000 free requests per day (1 request = 1 transcription) without entering any card information. [Taken Nov 2024](https://huggingface.co/docs/api-inference/rate-limits)

    - Optionaly also change the automatic speech recognition (ASR) model and/or language. 

    - After enabling transcriptions, restart the `rfmon.service`.

    - (*Optional*): Transcribe existing audio recordings by running the script `./transcribe.sh` inside the util folder. 

By following these steps, you will have RFMON installed and configured properly.
