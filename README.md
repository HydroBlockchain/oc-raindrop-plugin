# Hydro Raindrop for October CMS

![October CMS Logo](https://octobercms.com/themes/website/assets/images/october-color-logo.svg)

Welcome to the Hydro Raindrop October CMS plugin repository on GitHub.

![Hydro Logo](https://i.imgur.com/slcCepB.png)

<div align="center">
  <a href="https://www.youtube.com/watch?v=d88jbPdxI88"><img src="https://img.youtube.com/vi/d88jbPdxI88/0.jpg" alt="Hydro Raindrop 2FA vs Google Authenticator"></a>
</div>

## Features

* Requires minimum hassle to setup
* Intercepts the RainLab.User Login automatically
* Allows users to set-up MFA with their HydroID
* Instant Authentication

> Note: Free Hydro Mobile App is required to complete the MFA process. You can get iOS App [here](https://goo.gl/LpAuzq) or the Android App [here](https://goo.gl/eNrdn2).

## Requirements

- PHP 7.1 or higher
- `RainLab.User` plugin installed and configured
- `RainLab.Translate` plugin installed and configured
- `HydroRaindropDemo` theme (optional) - [Hydro Raindrop Demo Theme](https://github.com/crypt0h3nk/oc-hydroraindropdemo-theme)

## Installation

There are different ways to install this plugin. We recommend to install it from October CMS back-end.

### From October CMS (recommended)

1. Log in to the back-end of October CMS.
2. Navigate to `Settings` in the Main Menu.
3. Scroll down to `SYSTEM` and click on `Updates & Plugins`.
4. Click the button `+ Install plugins`.
5. Search for 'Raindrop'.
6. Select the plugin and it will begin installing.

### Using Composer

Execute the following commands from CLI (make sure your current working directory is at the October CMS root):

- `composer require hydrocommunity/oc-hydroraindrop-plugin`
- `php artisan october:up`
- `composer update`

### Manually

- Download latest releases directly from GitHub.com and unzip the release into the `plugins` directory. 

- `php artisan october:up`
- `composer update`

## Configuration

### 1. Settings

1. Log in to the back-end of October CMS.
2. Navigate to `Settings` in the Main Menu.
3. Scroll down to `USERS` and click on `Hydro Raindrop`.
4. At the tab `General` select the Login Page and the Redirect Page.
5. Enter your `Application ID`, `Client ID` and `Client Secret` in the `API Settings` tab.
6. Make some adjustments for your project in the `Customization` tab.

### 2. Implementation into your theme.

Install the `HydroRaindropDemo` theme which includes an example implementation of Hydro Raindrop MFA.

## Events

| Event | Payload | Description
|---|---|---|
| `hydrocommunity.raindrop.user.mfa.enabled` | `User` | Fired when user successfully enabled MFA on their account. |
| `hydrocommunity.raindrop.user.mfa.disabled` | `User` | Fired when user has disabled MFA on their account. |
| `hydrocommunity.raindrop.user.mfa.setup-required` | `User` | Fired when user needs to set up their HydroID. |
| `hydrocommunity.raindrop.user.mfa.required` | `User` | Fired when user needs to perform MFA. |
| `hydrocommunity.raindrop.user.blocked` | `User` | Fired after a user is blocked due to many failed MFA attempts. |
| `hydrocommunity.raindrop.mfa.session-timed-out` | `User` | Fired after the MFA session has been timed out. |

## Commands

| Command | Description
|---|---|
| `hydro-community:raindrop:install-pages` | Install required pages for the Hydro Raindrop plugin. |
