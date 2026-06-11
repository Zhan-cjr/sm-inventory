module.exports = {
  apps: [
    {
      name: "company-profile",
      script: "node_modules/next/dist/bin/next",
      args: "start -p 3002",
      cwd: "./",
      env: {
        NODE_ENV: "production",
      }
    }
  ]
};
