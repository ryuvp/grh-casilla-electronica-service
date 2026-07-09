# ---------- Stage 1: Build con Node ----------
FROM node:20-alpine AS build

WORKDIR /app

# Copiamos solo dependencias primero para aprovechar caché Docker
COPY frontend/package*.json ./
RUN npm ci

# Copiar el resto del código del frontend
COPY frontend/ .

# Copiar env de producción
COPY frontend/.env.prod .env

# Construir el frontend
RUN npm run build

# ---------- Stage 2: Imagen final con Nginx ----------
FROM nginx:stable-alpine

# Copiar configuración de Nginx
COPY docker/frontend/k8s-default.conf /etc/nginx/conf.d/default.conf

# Copiar build generado en el stage anterior
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
