
# ------------------------------------------------------#
# Build the DXPR Builder project
# ------------------------------------------------------#
FROM node:14

ARG TARGET_PATH
ARG ENTRYPOINT_SH

USER root

RUN mkdir -p $TARGET_PATH
WORKDIR $TARGET_PATH
COPY $ENTRYPOINT_SH /
COPY qa-demo.wait.sh /

ENV ENTRYPOINT_SH=$ENTRYPOINT_SH
ENTRYPOINT bash -c "/$ENTRYPOINT_SH"
