FROM node:22-alpine

WORKDIR /var/www/html

# Install dependencies from package.docker.json
COPY package.docker.json ./package.json
RUN npm install

# The actual source code is mounted as a volume
CMD ["npm", "run", "dev"]
