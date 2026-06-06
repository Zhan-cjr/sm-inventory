module.exports = {
  apps: [
    {
      name: "cloudflared-tunnel",
      script: "C:\\WINDOWS\\SYSTEM32\\cloudflared.exe",
      args: "tunnel run smattendancev2-tunnel",
      autorestart: true
    }
  ]
};
