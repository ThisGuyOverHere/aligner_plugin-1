import React, {Component} from 'react';
import PropTypes from "prop-types";

class SplitDivisor extends Component {
    static propTypes = {
        position: PropTypes.number,
        onDivisorClick: PropTypes.func,
        isIcon: PropTypes.bool,
    };

    constructor(props) {
        super(props);
        this.state = {};
    }

    componentDidMount() {
    }

    componentWillUnmount() {

    }

    render = () => {
        const {temporary} = this.props;
        let classes = ['icon-divisor'];
        if (temporary) {
            classes.push('inHover');
        }
        return (
            <span className="icon-container">
                <span
                    style={{
                        display: !this.props.isIcon ? 'block' : 'none'
                    }}
                    className={classes.join(" ")}
                    onClick={() => {
                        this.props.onClick(this.props.position)
                    }}>
                    <i className="icon text cursor"></i>
                </span>
                <span
                    style={{
                        display: this.props.isIcon ? 'block' : 'none'
                    }}
                    className={classes.join(" ") + ' splitted'}
                    onClick={() => {
                        this.props.onClick(this.props.position)
                    }}>
                    <i className="icon arrows alternate horizontal"></i>
                </span>
            </span>
        );
    };


}

export default SplitDivisor;