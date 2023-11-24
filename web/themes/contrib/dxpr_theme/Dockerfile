
ARG PHP_TAG

# ------------------------------------------------------#
# Build the DXPR Builder project in as a first build stage
# ------------------------------------------------------#
FROM node:14 as build

ARG DXPR_THEME_CONTAINER

USER root

RUN mkdir -p $DXPR_THEME_CONTAINER
WORKDIR $DXPR_THEME_CONTAINER

# Copy DXPR Builder source code (keep in image)
COPY . $DXPR_THEME_CONTAINER

RUN npm install \
  && npx grunt babel ; npx grunt terser ; npx grunt sass ; npx grunt postcss


# ------------------------------------------------------#
# Copy the DXPR Builder to run as the same user wodby
# used in the php and nginx containers
#
# TODO: try to use alpine image and create a wodby user
# and group with 1000 as UID and GID
# ------------------------------------------------------#
FROM wodby/drupal-php:$PHP_TAG

ARG DXPR_THEME_CONTAINER

USER root

RUN mkdir -p $DXPR_THEME_CONTAINER
WORKDIR $DXPR_THEME_CONTAINER

# Copy DXPR Builder source code (keep in image)
COPY --from=build $DXPR_THEME_CONTAINER $DXPR_THEME_CONTAINER

RUN chown -R wodby:wodby $DXPR_THEME_CONTAINER

USER wodby

ENTRYPOINT [ "/bin/echo", "dxpr-theme volume is now ready..." ]

