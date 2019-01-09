import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import Hotkeys from "react-hot-keys";

class ToolbarActionsHide extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired,
        jobConf: PropTypes.shape({
            password: PropTypes.string,
            id: PropTypes.any
        })
    };

    constructor(props) {
        super(props);
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        //console.log(this.props.selection.source);
        //console.log(this.props.selection.target);
        let disabled = false;
        if (!(this.props.selection.source.count > 0 || this.props.selection.target.count > 0)) {
            disabled = true;
        }
        return <Hotkeys
            keyName="alt+h"
            onKeyDown={this.onHideClick}>
            <button
                disabled={disabled}
                onClick={this.onHideClick}>
                Hide
            </button>
        </Hotkeys>;
    }

    onHideClick = () => {
        let matches = [];
        this.props.selection.source.list.map((item, index) => {
            matches.push({
                source: item,
                target: '',
                to_hide: ''
            })
        });

        this.props.selection.target.list.map((item, index) => {
            if (matches[index]) {
                matches[index].target = item;
            } else {
                matches.push({
                    source: '',
                    target: item,
                    to_hide: ''
                })
            }
        });

        matches.map((item, index) => {
            if (item.source === item.target) {
                item.to_hide = 'both';
            } else if (item.source) {
                item.target = item.source;
                item.to_hide = 'source';
            } else {
                item.source = item.target;
                item.to_hide = 'target';
            }
        });

        /*const type = this.props.selection.source.count > 0 ? 'source' : 'target';
        const orders = this.props.selection[type].list.sort((a, b) => {
            return a - b
        });*/

        ProjectActions.hideSegments(matches);
        ProjectActions.addSegmentToSelection(-1);
        ProjectActions.onActionHover(null);
    }
}

export default ToolbarActionsHide;
